<?php

namespace App\Modules\Pub\Proposal\Services;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Support\Facades\DB;

/**
 * Привязка КП к сделке Битрикса.
 *
 * Связь 1:1: одна сделка — одно КП (группа со всеми итерациями).
 * Сделок больше 700, поэтому поиск идёт на стороне БД с фильтрами
 * по менеджеру, компании и ключевым словам.
 */
class ProposalDealService
{
    /** Сколько сделок отдавать в выдаче поиска */
    public const SEARCH_LIMIT = 50;

    /**
     * Поиск сделок для привязки
     *
     * @param array $params [
     *     'q' => строка поиска (название, ID, компания, конечный заказчик),
     *     'manager' => assigned_by (полное значение из crm_deal),
     *     'company' => подстрока названия компании,
     *     'stage' => stage_name,
     *     'only_free' => bool — только непривязанные сделки,
     *     'proposal_group' => текущая группа КП (её привязка не считается занятой),
     * ]
     * @return \Illuminate\Support\Collection
     */
    public static function search(array $params = [])
    {
        $q = trim((string) ($params['q'] ?? ''));
        $onlyFree = (bool) ($params['only_free'] ?? true);

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

        // --- какие сделки уже заняты ---------------------------------------
        $taken = static::takenDealIds($params['proposal_group'] ?? null);

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
     * Сделки, уже привязанные к другим КП: [deal_id => Proposal]
     *
     * @param string|null $exceptGroup Группа, привязку которой не считаем занятой
     * @return \Illuminate\Support\Collection
     */
    public static function takenDealIds(string $exceptGroup = null)
    {
        $builder = Proposal::query()
            ->whereNotNull('crm_deal_id')
            ->when($exceptGroup, fn($builder) => $builder->where('group', '!=', $exceptGroup))
            ->select(['crm_deal_id', 'group', 'name', 'number'])
            ->get();

        return $builder->keyBy('crm_deal_id');
    }

    /**
     * Привязать сделку к КП (всем итерациям группы)
     *
     * @param Proposal $proposal Любая итерация
     * @param int $dealId ID сделки Битрикса
     * @return Proposal
     */
    public static function attach(Proposal $proposal, int $dealId): Proposal
    {
        $deal = CrmDeal::find($dealId);
        if (empty($deal)) {
            throw new \InvalidArgumentException('Сделка #' . $dealId . ' не найдена');
        }

        $taken = static::takenDealIds($proposal->group);
        if ($taken->has($dealId)) {
            $other = $taken->get($dealId);
            throw new \InvalidArgumentException(
                'Сделка уже привязана к КП «' . $other->name . '». Связь 1:1, сначала отвяжите её там.'
            );
        }

        Proposal::where('group', $proposal->group)->update([
            'crm_deal_id' => $dealId,
            'crm_deal_linked_at' => now(),
            'crm_deal_linked_by' => auth()->id(),
        ]);

        return $proposal->refresh();
    }

    /**
     * Отвязать сделку
     *
     * @param Proposal $proposal
     * @return Proposal
     */
    public static function detach(Proposal $proposal): Proposal
    {
        Proposal::where('group', $proposal->group)->update([
            'crm_deal_id' => null,
            'crm_deal_linked_at' => null,
            'crm_deal_linked_by' => null,
        ]);

        return $proposal->refresh();
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
