<?php

namespace App\Modules\Pub\Proposal\Services;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;

/**
 * Привязка КП к сделкам Битрикса.
 *
 * У одного КП может быть несколько сделок (разбили поставку на этапы,
 * лицензии и услуги ведут отдельными сделками и т.п.). Обратное неверно:
 * одна сделка принадлежит одному КП, иначе сверка с CRM начнёт двоить суммы.
 *
 * Привязка живёт на группе КП — то есть на всех итерациях сразу.
 * Главная сделка дублируется в proposals.crm_deal_id: её читают старый код,
 * фильтры списка и колонка «сделка».
 */
class ProposalDealService
{
    /** Сколько сделок отдавать в выдаче поиска */
    public const SEARCH_LIMIT = 50;

    /**
     * Привязки КП: строки pivot с подтянутой сделкой
     *
     * @param Proposal|string $proposal КП или его group
     * @return \Illuminate\Support\Collection
     */
    public static function links($proposal)
    {
        $group = $proposal instanceof Proposal ? $proposal->group : (string) $proposal;

        $links = ProposalCrmDeal::forGroup($group)->get();
        if ($links->isEmpty()) return collect();

        // сделки лежат в другой БД — забираем одним запросом и раздаём
        $deals = CrmDeal::whereIn('id', $links->pluck('crm_deal_id'))->get()->keyBy('id');

        return $links->map(function ($link) use ($deals) {
            $link->deal = $deals->get($link->crm_deal_id);
            return $link;
        })->values();
    }

    /**
     * ID привязанных сделок
     *
     * @param Proposal|string $proposal
     * @return \Illuminate\Support\Collection
     */
    public static function dealIds($proposal)
    {
        $group = $proposal instanceof Proposal ? $proposal->group : (string) $proposal;

        return ProposalCrmDeal::where('proposal_group', $group)->pluck('crm_deal_id');
    }

    /**
     * Поиск сделок для привязки
     *
     * @param array $params [
     *     'q' => строка поиска (название, ID, компания, конечный заказчик),
     *     'manager' => assigned_by (полное значение из crm_deal),
     *     'company' => подстрока названия компании,
     *     'stage' => stage_name,
     *     'only_free' => bool — только непривязанные сделки,
     *     'proposal_group' => текущая группа КП (её привязки не считаются занятыми),
     * ]
     * @return \Illuminate\Support\Collection
     */
    public static function search(array $params = [])
    {
        $q = trim((string) ($params['q'] ?? ''));
        $onlyFree = (bool) ($params['only_free'] ?? true);
        $group = $params['proposal_group'] ?? null;

        $builder = CrmDeal::query()
            ->leftJoin('crm_deal_uf', 'crm_deal.id', '=', 'crm_deal_uf.deal_id')
            ->select([
                'crm_deal.id',
                'crm_deal.title',
                'crm_deal.company_name',
                'crm_deal.assigned_by',
                'crm_deal.stage_name',
                'crm_deal.stage_semantic_id',
                'crm_deal.opportunity',
                'crm_deal.currency_id',
                'crm_deal.begindate',
                'crm_deal.closedate',
                'crm_deal.date_create',
                // конечный заказчик
                'crm_deal_uf.uf_crm_1717755645 as customer_name',
                // плановый квартал исполнения
                'crm_deal_uf.uf_crm_1722255711522 as plan_quarter',
                // стоимость лицензий (без НДС)
                'crm_deal_uf.uf_crm_1718977752420 as amount_licenses',
                // стоимость услуг (с НДС)
                'crm_deal_uf.uf_crm_1718977763677 as amount_services',
            ]);

        // --- поиск по ключевым словам --------------------------------------
        if ($q !== '') {
            // числовой ввод — скорее всего ID сделки
            if (ctype_digit($q)) {
                $builder->where(function ($builder) use ($q) {
                    $builder->where('crm_deal.id', (int) $q)
                        ->orWhere('crm_deal.title', 'like', '%' . $q . '%');
                });
            } else {
                // каждое слово должно встретиться хотя бы в одном из полей
                $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);

                foreach ($words as $word) {
                    $builder->where(function ($builder) use ($word) {
                        $like = '%' . $word . '%';
                        $builder->where('crm_deal.title', 'like', $like)
                            ->orWhere('crm_deal.company_name', 'like', $like)
                            ->orWhere('crm_deal_uf.uf_crm_1717755645', 'like', $like);
                    });
                }
            }
        }

        // --- уже привязанные к этому КП сделки в выдаче не нужны -------------
        $own = $group ? static::dealIds($group) : collect();
        if ($own->isNotEmpty()) {
            $builder->whereNotIn('crm_deal.id', $own->all());
        }

        // --- фильтры --------------------------------------------------------
        if (!empty($params['manager'])) {
            $builder->whereIn('crm_deal.assigned_by', (array) $params['manager']);
        }

        if (!empty($params['company'])) {
            $like = '%' . $params['company'] . '%';
            $builder->where(function ($builder) use ($like) {
                $builder->where('crm_deal.company_name', 'like', $like)
                    ->orWhere('crm_deal_uf.uf_crm_1717755645', 'like', $like);
            });
        }

        if (!empty($params['stage'])) {
            $builder->whereIn('crm_deal.stage_name', (array) $params['stage']);
        }

        $builder->orderByDesc('crm_deal.date_create')->limit(static::SEARCH_LIMIT * 3);

        $rows = $builder->get();

        // --- какие сделки уже заняты другими КП -----------------------------
        $taken = static::takenDealIds($group);

        $rows = $rows->map(function ($row) use ($taken) {
            $row->is_taken = $taken->has($row->id);
            $row->taken_by = $taken->get($row->id);
            return $row;
        });

        if ($onlyFree) {
            $rows = $rows->where('is_taken', false);
        }

        return $rows->take(static::SEARCH_LIMIT)->values();
    }

    /**
     * Сделки, привязанные к другим КП: [deal_id => Proposal]
     *
     * @param string|null $exceptGroup Группа, привязки которой не считаем занятыми
     * @return \Illuminate\Support\Collection
     */
    public static function takenDealIds(string $exceptGroup = null)
    {
        $links = ProposalCrmDeal::query()
            ->when($exceptGroup, fn($builder) => $builder->where('proposal_group', '!=', $exceptGroup))
            ->get(['crm_deal_id', 'proposal_group']);

        if ($links->isEmpty()) return collect();

        $proposals = Proposal::whereIn('group', $links->pluck('proposal_group')->unique())
            ->latestIteration()
            ->get(['group', 'name', 'number'])
            ->keyBy('group');

        return $links->mapWithKeys(fn($link) => [
            $link->crm_deal_id => $proposals->get($link->proposal_group),
        ]);
    }

    /**
     * Привязать сделку к КП (ко всей группе)
     *
     * @param Proposal $proposal Любая итерация
     * @param int $dealId ID сделки Битрикса
     * @param bool $main Сделать главной
     * @return ProposalCrmDeal
     */
    public static function attach(Proposal $proposal, int $dealId, bool $main = false): ProposalCrmDeal
    {
        $deal = CrmDeal::find($dealId);
        if (empty($deal)) {
            throw new \InvalidArgumentException('Сделка #' . $dealId . ' не найдена');
        }

        $taken = static::takenDealIds($proposal->group);
        if ($taken->has($dealId)) {
            $other = $taken->get($dealId);
            throw new \InvalidArgumentException(
                'Сделка уже привязана к КП «' . ($other->name ?? 'другое КП') . '». Сначала отвяжите её там.'
            );
        }

        $existing = ProposalCrmDeal::forGroup($proposal->group)->get();

        $link = ProposalCrmDeal::firstOrNew([
            'proposal_group' => $proposal->group,
            'crm_deal_id' => $dealId,
        ]);

        $link->fill([
            // первая привязка автоматически становится главной
            'is_main' => $main || $existing->isEmpty(),
            'linked_at' => now(),
            'linked_by' => auth()->id(),
        ])->save();

        if ($link->is_main) static::setMain($proposal, $dealId);
        else static::syncMain($proposal->group);

        return $link;
    }

    /**
     * Отвязать сделку. Без $dealId снимает все привязки.
     *
     * @param Proposal $proposal
     * @param int|null $dealId
     * @return void
     */
    public static function detach(Proposal $proposal, int $dealId = null): void
    {
        ProposalCrmDeal::where('proposal_group', $proposal->group)
            ->when($dealId, fn($builder) => $builder->where('crm_deal_id', $dealId))
            ->delete();

        static::syncMain($proposal->group);
    }

    /**
     * Назначить главную сделку
     *
     * @param Proposal $proposal
     * @param int $dealId
     * @return void
     */
    public static function setMain(Proposal $proposal, int $dealId): void
    {
        ProposalCrmDeal::where('proposal_group', $proposal->group)
            ->update(['is_main' => false]);

        ProposalCrmDeal::where('proposal_group', $proposal->group)
            ->where('crm_deal_id', $dealId)
            ->update(['is_main' => true]);

        static::syncMain($proposal->group);
    }

    /**
     * Продублировать главную сделку в proposals.crm_deal_id.
     *
     * Колонка осталась ради старого кода: колонка «сделка» в списке,
     * фильтры и выгрузки читают именно её.
     *
     * @param string $group
     * @return void
     */
    public static function syncMain(string $group): void
    {
        $main = ProposalCrmDeal::forGroup($group)->first();

        // если главная не выбрана, но привязки есть — делаем главной первую
        if ($main && !$main->is_main) {
            $main->update(['is_main' => true]);
        }

        Proposal::where('group', $group)->update([
            'crm_deal_id' => $main?->crm_deal_id,
            'crm_deal_linked_at' => $main?->linked_at,
            'crm_deal_linked_by' => $main?->linked_by,
        ]);
    }

    /**
     * Список менеджеров Битрикса для фильтра
     *
     * @return \Illuminate\Support\Collection
     */
    public static function managers()
    {
        return CrmDeal::query()
            ->whereNotNull('assigned_by')
            ->distinct()
            ->orderBy('assigned_by')
            ->pluck('assigned_by');
    }

    /**
     * Список стадий для фильтра
     *
     * @return \Illuminate\Support\Collection
     */
    public static function stages()
    {
        return CrmDeal::query()
            ->whereNotNull('stage_name')
            ->distinct()
            ->orderBy('stage_name')
            ->pluck('stage_name');
    }
}
