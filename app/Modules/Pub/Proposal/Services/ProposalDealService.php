<?php

namespace App\Modules\Pub\Proposal\Services;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
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
    /**
     * Сколько сделок отдавать в выдаче поиска.
     * По умолчанию; рабочее значение — consts.proposal_deal_search_limit (читать через searchLimit())
     */
    public const SEARCH_LIMIT = 50;

    /**
     * Сколько строк отдавать в выдаче поиска сделок и КП
     * (consts.proposal_deal_search_limit, по умолчанию SEARCH_LIMIT)
     *
     * @return int
     */
    public static function searchLimit(): int
    {
        return max(1, Constant::int('proposal_deal_search_limit', static::SEARCH_LIMIT));
    }

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
        $limit = static::searchLimit();
        $customer_field = 'crm_deal_uf.' . CrmDealRegistryService::ufCustomer();

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
                $customer_field . ' as customer_name',
                // плановый квартал исполнения
                'crm_deal_uf.' . DealProjectService::ufQuarter() . ' as plan_quarter',
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
                    $builder->where(function ($builder) use ($word, $customer_field) {
                        $like = '%' . $word . '%';
                        $builder->where('crm_deal.title', 'like', $like)
                            ->orWhere('crm_deal.company_name', 'like', $like)
                            ->orWhere($customer_field, 'like', $like);
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
            $builder->where(function ($builder) use ($like, $customer_field) {
                $builder->where('crm_deal.company_name', 'like', $like)
                    ->orWhere($customer_field, 'like', $like);
            });
        }

        if (!empty($params['stage'])) {
            $builder->whereIn('crm_deal.stage_name', (array) $params['stage']);
        }

        $builder->orderByDesc('crm_deal.date_create')->limit($limit * 3);

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

        return $rows->take($limit)->values();
    }

    /**
     * Поиск КП под сделку — обратная сторона search() (patch v24).
     *
     * Реестр сделок привязывает КП к сделке, а не наоборот, поэтому здесь
     * ищутся именно КП: по номеру, названию, партнёру и заказчику. Отдаётся
     * последняя итерация каждой группы — привязка всё равно живёт на группе.
     *
     * Правило «одна сделка принадлежит одному КП» проверяется при привязке,
     * а не здесь: занятых КП не бывает, у КП сделок может быть несколько.
     *
     * @param array $params [
     *     'q' => строка поиска (номер, название, партнёр, заказчик),
     *     'partner_id' => отбор по партнёру портала,
     *     'limit' => сколько строк отдать,
     * ]
     * @return \Illuminate\Support\Collection
     */
    public static function searchProposals(array $params = [])
    {
        $q = trim((string) ($params['q'] ?? ''));
        $limit = (int) ($params['limit'] ?? static::searchLimit());

        $builder = Proposal::query()
            ->latestIteration()
            ->with(['partner', 'company', 'manager'])
            ->when(!empty($params['partner_id']), fn($builder) => $builder->where('partner_id', (int) $params['partner_id']));

        if ($q !== '') {
            // каждое слово должно найтись хотя бы в одном из полей
            $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($words as $word) {
                $like = '%' . $word . '%';

                $builder->where(function ($builder) use ($like) {
                    $builder->where('proposals.number', 'like', $like)
                        ->orWhere('proposals.name', 'like', $like)
                        ->orWhere('proposals.name_alt', 'like', $like)
                        ->orWhereHas('partner', fn($builder) => $builder->where('name', 'like', $like))
                        ->orWhereHas('company', fn($builder) => $builder->where('name', 'like', $like));
                });
            }
        }

        $rows = $builder->orderByDesc('proposals.id')->limit($limit)->get();
        if ($rows->isEmpty()) return collect();

        // сколько сделок уже привязано к каждой группе
        $counts = ProposalCrmDeal::whereIn('proposal_group', $rows->pluck('group')->all())
            ->get(['proposal_group'])
            ->countBy('proposal_group');

        return $rows->map(function (Proposal $proposal) use ($counts) {
            $proposal->deals_count = (int) ($counts[$proposal->group] ?? 0);

            return $proposal;
        })->values();
    }

    /**
     * КП, к которому привязана сделка (последняя итерация группы)
     *
     * @param int $deal_id
     * @return Proposal|null
     */
    public static function proposalOfDeal(int $deal_id): ?Proposal
    {
        $group = ProposalCrmDeal::where('crm_deal_id', $deal_id)->value('proposal_group');

        return $group ? static::lastProposal($group) : null;
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
        // patch v29: удаление без событий модели — журнал изменений оборачивается явно (корень — последняя редакция)
        EntityLogService::around(static::lastProposal($proposal), fn() => ProposalCrmDeal::where('proposal_group', $proposal->group)
            ->when($dealId, fn($builder) => $builder->where('crm_deal_id', $dealId))
            ->delete());

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
        // patch v29: массовый update без событий модели — журнал изменений оборачивается явно (корень — последняя редакция)
        EntityLogService::around(static::lastProposal($proposal), function () use ($proposal, $dealId) {
            ProposalCrmDeal::where('proposal_group', $proposal->group)
                ->update(['is_main' => false]);

            ProposalCrmDeal::where('proposal_group', $proposal->group)
                ->where('crm_deal_id', $dealId)
                ->update(['is_main' => true]);
        });

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
        // patch v29: массовый update без событий модели — журнал изменений оборачивается явно (корень — последняя редакция)
        EntityLogService::around(static::lastProposal($group), function () use ($group) {
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
        });
    }

    /**
     * Последняя итерация КП группы
     *
     * @param Proposal|string $proposal
     * @return Proposal|null
     */
    public static function lastProposal($proposal)
    {
        $group = $proposal instanceof Proposal ? $proposal->group : (string) $proposal;

        return Proposal::where('group', $group)
            ->orderByDesc('iteration')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Последний созданный вариант КП.
     *
     * Со сделкой Битрикса всегда сверяется именно он — не основной вариант
     * и не первый: в CRM уходит то, что посчитали последним.
     *
     * @param Proposal|string $proposal
     * @return mixed|null
     */
    public static function lastVariant($proposal)
    {
        $proposal = $proposal instanceof Proposal ? $proposal : static::lastProposal($proposal);
        if (empty($proposal)) return null;

        // берём последнюю итерацию группы, даже если передали старую
        $last = static::lastProposal($proposal) ?: $proposal;

        return $last->variants()->orderByDesc('id')->first();
    }

    /**
     * Сверка сделок Битрикса с последним вариантом КП
     *
     * Сумма сделок должна совпадать с суммой последнего варианта КП
     * и быть в той же валюте — без пересчёта курса: в CRM лежит ровно то,
     * что отправили заказчику.
     *
     * @param \Illuminate\Support\Collection $links Привязки (см. links())
     * @param Proposal|string $proposal
     * @return array [
     *     'variant' => последний вариант,
     *     'currency' => валюта КП,
     *     'amount' => сумма последнего варианта,
     *     'deals_amount' => сумма сделок,
     *     'diff' => расхождение,
     *     'errors' => [deal_id => ['текст ошибки', ...]],
     *     'has_errors' => bool,
     *     'summary' => краткий текст для шага цепочки,
     * ]
     */
    public static function check($links, $proposal): array
    {
        $last = $proposal instanceof Proposal ? (static::lastProposal($proposal) ?: $proposal) : static::lastProposal($proposal);
        $variant = static::lastVariant($last);

        $currency = strtoupper((string) ($variant->currency_slug ?? $last?->currency_slug ?? 'RUB'));
        $amount = (float) ($variant->cost_total ?? 0);

        $errors = [];
        $deals_amount = 0.0;
        $with_amount = 0;

        foreach ($links as $link) {
            $deal = $link->deal;
            $list = [];

            if (empty($deal)) {
                $errors[$link->crm_deal_id] = ['Сделки нет в выгрузке Битрикс24 — сверить сумму не с чем'];
                continue;
            }

            $deal_currency = strtoupper((string) $deal->currency_id);
            $deal_amount = (float) $deal->opportunity;

            $deals_amount += $deal_amount;
            if ($deal_amount > 0) $with_amount++;

            if ($deal_currency !== '' && $deal_currency !== $currency) {
                $list[] = 'Валюта сделки ' . $deal_currency . ', у КП ' . $currency;
            }

            if ($deal_amount <= 0) {
                $list[] = 'В сделке не указана сумма';
            }

            if (!empty($list)) $errors[$link->crm_deal_id] = $list;
        }

        $diff = $deals_amount - $amount;

        // сумма расходится: при одной сделке ошибка на ней, при нескольких —
        // на всех, потому что виновата любая из них
        if ($links->isNotEmpty() && $amount > 0 && abs($diff) > 1) {
            $text = $links->count() === 1
                ? 'Сумма сделки ' . static::money($deals_amount) . ' ≠ сумме КП ' . static::money($amount)
                    . ' (расхождение ' . static::money($diff, true) . ')'
                : 'Сумма сделок ' . static::money($deals_amount) . ' ≠ сумме КП ' . static::money($amount)
                    . ' (расхождение ' . static::money($diff, true) . ')';

            foreach ($links as $link) {
                $errors[$link->crm_deal_id][] = $text;
            }
        }

        return [
            'proposal' => $last,
            'variant' => $variant,
            'currency' => $currency,
            'amount' => $amount,
            'deals_amount' => $deals_amount,
            'deals_with_amount' => $with_amount,
            'diff' => $diff,
            'errors' => $errors,
            'has_errors' => !empty($errors),
            'summary' => match (true) {
                $links->isEmpty() => null,
                empty($errors) => 'Суммы сходятся: ' . static::money($amount) . ' ' . $currency,
                default => 'Расхождение с Битрикс24 по ' . tools()->num_rus(count($errors), ['сделкам', 'сделке', 'сделкам'], 1),
            },
        ];
    }

    /**
     * Число для текста ошибки
     *
     * @param float $value
     * @param bool $sign Показать знак
     * @return string
     */
    protected static function money(float $value, bool $sign = false): string
    {
        $text = number_format(round($value), 0, ',', ' ');

        return $sign && $value > 0 ? '+' . $text : $text;
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
