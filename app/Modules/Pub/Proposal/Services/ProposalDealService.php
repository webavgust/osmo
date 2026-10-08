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
 * Связь многие-ко-многим (patch v43, решение владельца 08.10.2026; правило
 * «одна сделка — одно КП» от 12.09.2026 отменено). У одного КП может быть
 * несколько сделок (разбили поставку на этапы, лицензии и услуги ведут
 * отдельными сделками и т.п.), и к одной сделке можно привязать несколько КП.
 * Чтобы сверка с CRM не двоила суммы, check() сверяет не одно КП, а кластер —
 * связную компоненту графа КП↔сделки (см. cluster()).
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
     *     'only_free' => bool — только сделки без КП (по умолчанию false),
     *     'proposal_group' => текущая группа КП (её сделки в выдачу не попадают),
     * ]
     * @return \Illuminate\Support\Collection Сделки; у каждой справочно
     *     proposals — другие КП сделки [{group, number, name, url}] (без текущего),
     *     is_taken = false и taken_by = null (оставлены для старых шаблонов)
     */
    public static function search(array $params = [])
    {
        $q = trim((string) ($params['q'] ?? ''));
        $onlyFree = (bool) ($params['only_free'] ?? false);
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

        // --- к каким ещё КП привязаны сделки (справочно, не запрет) ---------
        $others = static::proposalsByDeal($group, $rows->pluck('id')->all());

        $rows = $rows->map(function ($row) use ($others) {
            $row->proposals = static::proposalRows($others->get($row->id, collect()));
            // patch v43: занятых сделок больше нет — поля оставлены для старых шаблонов
            $row->is_taken = false;
            $row->taken_by = null;
            return $row;
        });

        if ($onlyFree) {
            $rows = $rows->filter(fn($row) => empty($row->proposals));
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
     * Запретов нет (patch v43): связь КП↔сделки многие-ко-многим. С сделкой
     * в параметрах каждая строка справочно знает, привязано ли КП уже к ней
     * и какие ещё сделки у КП есть.
     *
     * @param array $params [
     *     'q' => строка поиска (номер, название, партнёр, заказчик),
     *     'partner_id' => отбор по партнёру портала,
     *     'limit' => сколько строк отдать,
     *     'deal_id' => сделка, под которую ищем (для attached_here / deals),
     * ]
     * @return \Illuminate\Support\Collection КП (последние итерации); у каждого
     *     deals_count — всего сделок КП, attached_here — КП уже привязано к deal_id,
     *     deals — ID других сделок КП (без deal_id), по возрастанию
     */
    public static function searchProposals(array $params = [])
    {
        $q = trim((string) ($params['q'] ?? ''));
        $limit = (int) ($params['limit'] ?? static::searchLimit());

        $builder = Proposal::query()
            ->latestIteration()
            // patch v33: второстепенное КП сделок не получает — в выборе его нет
            ->counted()
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

        $dealId = (int) ($params['deal_id'] ?? 0);

        // какие сделки уже привязаны к каждой группе
        $links = ProposalCrmDeal::whereIn('proposal_group', $rows->pluck('group')->all())
            ->get(['proposal_group', 'crm_deal_id'])
            ->groupBy('proposal_group');

        return $rows->map(function (Proposal $proposal) use ($links, $dealId) {
            $ids = $links->get($proposal->group, collect())->pluck('crm_deal_id')->map(fn($id) => (int) $id);

            $proposal->deals_count = $ids->count();
            $proposal->attached_here = $dealId > 0 && $ids->contains($dealId);
            $proposal->deals = $ids->reject(fn($id) => $id === $dealId)->sort()->values()->all();

            return $proposal;
        })->values();
    }

    /**
     * Все КП сделки: последние итерации групп (patch v43).
     * Порядок предсказуемый: свежие по дате отправки выше, затем по номеру.
     *
     * @param int $dealId ID сделки Битрикса
     * @return \Illuminate\Support\Collection<Proposal>
     */
    public static function proposalsOfDeal(int $dealId)
    {
        $groups = ProposalCrmDeal::where('crm_deal_id', $dealId)->pluck('proposal_group')->unique();
        if ($groups->isEmpty()) return collect();

        return static::orderProposals(
            Proposal::whereIn('group', $groups->all())->latestIteration()->get()
        );
    }

    /**
     * Первое КП сделки — для старых вызовов, где КП ждали одно.
     * Новый код берёт proposalsOfDeal().
     *
     * @param int $deal_id
     * @return Proposal|null
     */
    public static function proposalOfDeal(int $deal_id): ?Proposal
    {
        return static::proposalsOfDeal($deal_id)->first();
    }

    /**
     * КП сделок: [deal_id => Collection<Proposal>] (patch v43).
     * В каждой коллекции — последние итерации, порядок как в proposalsOfDeal().
     *
     * @param string|null $exceptGroup Группа, которую не включаем (текущее КП)
     * @param array|null $dealIds Ограничить этими сделками; null — все привязки
     * @return \Illuminate\Support\Collection
     */
    public static function proposalsByDeal(string $exceptGroup = null, array $dealIds = null)
    {
        if ($dealIds !== null && empty($dealIds)) return collect();

        $links = ProposalCrmDeal::query()
            ->when($exceptGroup, fn($builder) => $builder->where('proposal_group', '!=', $exceptGroup))
            ->when($dealIds !== null, fn($builder) => $builder->whereIn('crm_deal_id', $dealIds))
            ->get(['crm_deal_id', 'proposal_group']);

        if ($links->isEmpty()) return collect();

        $proposals = static::orderProposals(
            Proposal::whereIn('group', $links->pluck('proposal_group')->unique()->all())->latestIteration()->get()
        );

        return $links->groupBy('crm_deal_id')->map(function ($rows) use ($proposals) {
            $groups = $rows->pluck('proposal_group')->flip();

            return $proposals->filter(fn($proposal) => $groups->has($proposal->group))->values();
        });
    }

    /**
     * Сделки, привязанные к другим КП: [deal_id => первое КП сделки].
     *
     * Устаревшая форма времён «одна сделка — одно КП»: ключи по-прежнему
     * означают «у сделки есть другое КП». Новый код берёт proposalsByDeal().
     *
     * @param string|null $exceptGroup Группа, привязки которой не учитываем
     * @return \Illuminate\Support\Collection
     */
    public static function takenDealIds(string $exceptGroup = null)
    {
        return static::proposalsByDeal($exceptGroup)->map(fn($list) => $list->first());
    }

    /**
     * КП сделки строками для ответов API и шаблонов (patch v43)
     *
     * @param int $dealId
     * @return array [{group, number, name, url, is_main}] — is_main: сделка главная у этого КП
     */
    public static function dealProposalRows(int $dealId): array
    {
        $main = ProposalCrmDeal::where('crm_deal_id', $dealId)->where('is_main', true)
            ->pluck('proposal_group')->flip();

        return collect(static::proposalRows(static::proposalsOfDeal($dealId)))
            ->map(fn($row) => $row + ['is_main' => $main->has($row['group'])])
            ->all();
    }

    /**
     * КП строками: [{group, number, name, url}]
     *
     * @param \Illuminate\Support\Collection $proposals Последние итерации
     * @return array
     */
    public static function proposalRows($proposals): array
    {
        return collect($proposals)->map(fn(Proposal $proposal) => [
            'group' => $proposal->group,
            'number' => $proposal->number,
            'name' => $proposal->name,
            'url' => route('proposal.detail', [$proposal, $proposal->iteration]),
        ])->values()->all();
    }

    /**
     * Единый порядок КП сделки: по дате отправки (свежие выше, без даты — в конце), затем по номеру
     *
     * @param \Illuminate\Support\Collection $proposals
     * @return \Illuminate\Support\Collection
     */
    protected static function orderProposals($proposals)
    {
        return $proposals->sort(function (Proposal $a, Proposal $b) {
            $at = $a->sended_at ? strtotime((string) $a->sended_at) : null;
            $bt = $b->sended_at ? strtotime((string) $b->sended_at) : null;

            if ($at !== $bt) {
                if ($at === null) return 1;
                if ($bt === null) return -1;
                return $bt <=> $at;
            }

            return [(string) $a->number, $a->id] <=> [(string) $b->number, $b->id];
        })->values();
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
        // patch v33: второстепенное КП — только просмотр (403 с текстом)
        ProposalLinkService::assertEditable($proposal);

        $deal = CrmDeal::find($dealId);
        if (empty($deal)) {
            throw new \InvalidArgumentException('Сделка #' . $dealId . ' не найдена');
        }

        // patch v43: сделка может быть общей у нескольких КП — запрета нет,
        // сверка сумм идёт по кластеру (см. check())

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
        // patch v33: второстепенное КП — только просмотр (403 с текстом)
        ProposalLinkService::assertEditable($proposal);

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
        // patch v33: второстепенное КП — только просмотр (403 с текстом)
        ProposalLinkService::assertEditable($proposal);

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
     * Кластер КП: связная компонента графа КП↔сделки (patch v43).
     *
     * Обход по proposal_crm_deals от группы КП: КП → его сделки → их КП → …
     * Нужен сверке сумм: если сделка общая у нескольких КП, сравнивать её
     * с одним КП нельзя — будет ложное расхождение и двойной счёт.
     * От разрастания защищает предел групп; дошли до него — truncated.
     *
     * @param string $group Группа КП
     * @param int $maxGroups Предел групп в кластере
     * @return array [
     *     'groups' => [group, ...] — первая всегда исходная,
     *     'deal_ids' => [deal_id, ...] — уникальные, по возрастанию,
     *     'truncated' => bool — обход остановлен пределом,
     * ]
     */
    public static function cluster(string $group, int $maxGroups = 30): array
    {
        $groups = [$group => true];
        $deals = [];
        $queue = [$group];
        $truncated = false;

        while (!empty($queue)) {
            // сделки новых КП
            $new_deals = ProposalCrmDeal::whereIn('proposal_group', $queue)
                ->pluck('crm_deal_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->reject(fn($id) => isset($deals[$id]))
                ->values();

            $queue = [];
            if ($new_deals->isEmpty()) break;

            foreach ($new_deals as $id) $deals[$id] = true;

            // КП новых сделок
            $new_groups = ProposalCrmDeal::whereIn('crm_deal_id', $new_deals->all())
                ->pluck('proposal_group')
                ->unique()
                ->reject(fn($item) => isset($groups[$item]));

            foreach ($new_groups as $item) {
                if (count($groups) >= $maxGroups) {
                    $truncated = true;
                    break 2;
                }

                $groups[$item] = true;
                $queue[] = $item;
            }
        }

        $deal_ids = array_keys($deals);
        sort($deal_ids);

        return [
            'groups' => array_keys($groups),
            'deal_ids' => $deal_ids,
            'truncated' => $truncated,
        ];
    }

    /**
     * Сверка сделок Битрикса с последним вариантом КП
     *
     * Сумма сделок должна совпадать с суммой последнего варианта КП
     * и быть в той же валюте — без пересчёта курса: в CRM лежит ровно то,
     * что отправили заказчику.
     *
     * patch v43: сделка может быть общей у нескольких КП. Тогда сверяется
     * кластер (см. cluster()): сумма уникальных сделок кластера против суммы
     * последних вариантов всех КП кластера. Кластер из одного КП считается
     * ровно как раньше — по переданным $links.
     *
     * @param \Illuminate\Support\Collection $links Привязки этого КП (см. links())
     * @param Proposal|string $proposal
     * @return array [
     *     'proposal' => последняя итерация КП,
     *     'variant' => последний вариант,
     *     'currency' => валюта КП,
     *     'amount' => сумма КП (у общего кластера — сумма всех КП кластера),
     *     'own_amount' => сумма последнего варианта только этого КП,
     *     'deals_amount' => сумма сделок (у кластера — уникальных сделок кластера),
     *     'deals_with_amount' => сколько сделок с суммой,
     *     'diff' => расхождение deals_amount − amount,
     *     'errors' => [deal_id => ['текст ошибки', ...]] (у кластера — и по сделкам других КП),
     *     'has_errors' => bool,
     *     'summary' => краткий текст для шага цепочки,
     *     'cluster' => [
     *         'shared' => bool — в кластере больше одного КП,
     *         'groups' => [group, ...],
     *         'proposals' => [{group, number, name, url, currency, amount}, ...] — КП кластера, первое — это КП,
     *         'others' => те же строки без этого КП,
     *         'deal_ids' => [deal_id, ...] — уникальные сделки кластера,
     *         'truncated' => bool,
     *     ],
     * ]
     */
    public static function check($links, $proposal): array
    {
        $last = $proposal instanceof Proposal ? (static::lastProposal($proposal) ?: $proposal) : static::lastProposal($proposal);
        $variant = static::lastVariant($last);

        $currency = strtoupper((string) ($variant->currency_slug ?? $last?->currency_slug ?? 'RUB'));
        $own_amount = (float) ($variant->cost_total ?? 0);

        // --- кластер: КП, связанные общими сделками ---------------------------
        $cluster = $last ? static::cluster((string) $last->group) : ['groups' => [], 'deal_ids' => [], 'truncated' => false];
        $shared = count($cluster['groups']) > 1;

        $cluster_rows = [];
        if ($last) {
            $cluster_rows[] = static::clusterRow($last, $variant, $currency, $own_amount);
        }

        $amount = $own_amount;

        if ($shared) {
            // сделки всего кластера, каждая один раз
            $deals = CrmDeal::whereIn('id', $cluster['deal_ids'])->get()->keyBy('id');
            $links = collect($cluster['deal_ids'])->map(fn($id) => (object) [
                'crm_deal_id' => $id,
                'deal' => $deals->get($id),
            ]);

            $others = Proposal::whereIn('group', array_slice($cluster['groups'], 1))->latestIteration()->get();

            foreach (static::orderProposals($others) as $other) {
                $other_variant = $other->variants()->orderByDesc('id')->first();
                $other_currency = strtoupper((string) ($other_variant->currency_slug ?? $other->currency_slug ?? 'RUB'));
                $other_amount = (float) ($other_variant->cost_total ?? 0);

                $cluster_rows[] = static::clusterRow($other, $other_variant, $other_currency, $other_amount);
                $amount += $other_amount;
            }
        }

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
            $what = $shared
                ? 'сумме КП ' . collect($cluster_rows)->map(fn($row) => $row['number'] ?: $row['name'])->implode(' + ')
                : 'сумме КП';

            $text = ($links->count() === 1 ? 'Сумма сделки ' : 'Сумма сделок ') . static::money($deals_amount)
                . ' ≠ ' . $what . ' ' . static::money($amount)
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
            'own_amount' => $own_amount,
            'deals_amount' => $deals_amount,
            'deals_with_amount' => $with_amount,
            'diff' => $diff,
            'errors' => $errors,
            'has_errors' => !empty($errors),
            'summary' => match (true) {
                $links->isEmpty() => null,
                empty($errors) => 'Суммы сходятся: ' . static::money($amount) . ' ' . $currency
                    . ($shared ? ' (вместе с КП ' . collect($cluster_rows)->slice(1)->map(fn($row) => $row['number'] ?: $row['name'])->implode(', ') . ')' : ''),
                default => 'Расхождение с Битрикс24 по ' . tools()->num_rus(count($errors), ['сделкам', 'сделке', 'сделкам'], 1),
            },
            'cluster' => [
                'shared' => $shared,
                'groups' => $cluster['groups'],
                'proposals' => $cluster_rows,
                'others' => array_slice($cluster_rows, 1),
                'deal_ids' => $cluster['deal_ids'],
                'truncated' => $cluster['truncated'],
            ],
        ];
    }

    /**
     * Строка КП кластера для check()
     *
     * @param Proposal $proposal Последняя итерация
     * @param mixed $variant Последний вариант
     * @param string $currency
     * @param float $amount
     * @return array
     */
    protected static function clusterRow(Proposal $proposal, $variant, string $currency, float $amount): array
    {
        return [
            'group' => $proposal->group,
            'number' => $proposal->number,
            'name' => $proposal->name,
            'url' => route('proposal.detail', [$proposal, $proposal->iteration]),
            'currency' => $currency,
            'amount' => $amount,
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
