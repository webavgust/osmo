<?php

namespace App\Modules\Pub\CrmMonitor\Services;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use Illuminate\Support\Collection;

/**
 * Монитор расхождений с Битрикс24.
 *
 * Сводная карточка показывает расхождения по одной сделке. Здесь то же самое,
 * но списком по всем КП: где сделка не привязана, где её нет в выгрузке,
 * где не сходятся валюта или сумма, где статус КП спорит со стадией сделки.
 *
 * Сверяется ПОСЛЕДНИЙ СОЗДАННЫЙ вариант последней редакции КП — тот, что
 * ушёл заказчику. Валюты не конвертируются: расхождение валют это ошибка,
 * а не повод пересчитать по курсу.
 *
 * Новых таблиц не нужно: читаем proposals, proposal_crm_deals и crm_deal.
 *
 * patch v43: сделка может быть общей у нескольких КП. Такие КП сверяются
 * кластером (ProposalDealService::cluster()): сумма уникальных сделок кластера
 * против суммы всех его КП; в итогах money() кластер считается один раз.
 */
class CrmMismatchService
{
    /**
     * Допустимое расхождение сумм, в единицах валюты.
     * По умолчанию; рабочее значение — consts.crm_amount_tolerance (читать через amountTolerance())
     */
    public const AMOUNT_TOLERANCE = 1;

    /** Статусы, для которых сделка в Битриксе обязательна */
    public const DEAL_REQUIRED = ['sent', 'negotiation', 'won'];

    /** Стадии Битрикса: успех / провал */
    public const SEMANTIC_SUCCESS = 'S';
    public const SEMANTIC_FAIL = 'F';

    /**
     * Допустимое расхождение сумм КП и сделок, в единицах валюты
     * (consts.crm_amount_tolerance, по умолчанию AMOUNT_TOLERANCE)
     *
     * @return float
     */
    public static function amountTolerance(): float
    {
        return max(0.0, Constant::float('crm_amount_tolerance', (float) static::AMOUNT_TOLERANCE));
    }

    /**
     * Виды расхождений
     *
     * @return array
     */
    public static function issues(): array
    {
        return [
            'amount' => [
                'label' => 'Сумма не сходится',
                'color' => 'danger',
                'icon' => 'fa-scale-unbalanced',
                'hint' => 'Сумма сделок в Битрикс24 отличается от последнего варианта КП',
            ],
            'currency' => [
                'label' => 'Разная валюта',
                'color' => 'danger',
                'icon' => 'fa-coins',
                'hint' => 'Валюта сделки не совпадает с валютой КП',
            ],
            'missing' => [
                'label' => 'Сделки нет в Битрикс24',
                'color' => 'dark',
                'icon' => 'fa-link-slash',
                'hint' => 'Привязка есть, но такой сделки нет в выгрузке: её удалили или не синхронизировали',
            ],
            'no_deal' => [
                'label' => 'Сделка не привязана',
                'color' => 'warning',
                'icon' => 'fa-unlink',
                'hint' => 'КП отправлено или выиграно, но сделки Битрикс24 у него нет',
            ],
            'stage' => [
                'label' => 'Статус спорит со стадией',
                'color' => 'warning',
                'icon' => 'fa-code-branch',
                'hint' => 'КП выиграно, а сделка провалена (или наоборот)',
            ],
            'no_variant' => [
                'label' => 'Нет расчёта',
                'color' => 'secondary',
                'icon' => 'fa-calculator',
                'hint' => 'У КП нет ни одного варианта — сверять нечего',
            ],
        ];
    }

    /**
     * КП с расхождениями
     *
     * @param array $params [
     *     'issue' => string|null — код расхождения,
     *     'status' => string|null — статус КП,
     *     'manager' => int|null — менеджер КП,
     *     'q' => string|null — поиск по названию, номеру, компании,
     *     'only_issues' => bool — показывать только проблемные (по умолчанию да),
     * ]
     * @return Collection
     */
    public static function rows(array $params = []): Collection
    {
        $builder = Proposal::query()
            ->latestIteration()
            // patch v33: второстепенное КП без сделок по правилу — с Битрикс24 не сверяется
            ->counted()
            ->with(['variants', 'company', 'manager']);

        if (!empty($params['status'])) {
            $builder->where('status', $params['status']);
        }

        if (!empty($params['manager'])) {
            $builder->where('manager_id', (int) $params['manager']);
        }

        if (!empty($params['q'])) {
            $like = '%' . trim($params['q']) . '%';
            $builder->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('number', 'like', $like)
                    ->orWhereHas('company', fn($builder) => $builder->where('name', 'like', $like));
            });
        }

        $proposals = $builder->get();
        if ($proposals->isEmpty()) return collect();

        // привязки и сделки — двумя запросами на всю выборку
        $links = ProposalCrmDeal::whereIn('proposal_group', $proposals->pluck('group'))
            ->orderByDesc('is_main')
            ->get()
            ->groupBy('proposal_group');

        $deals = collect();
        $deal_ids = $links->flatten()->pluck('crm_deal_id')->unique();

        // patch v43: КП, связанные общими сделками, сверяются кластером
        $clusters = static::clusters($proposals, $deal_ids);
        $deal_ids = $deal_ids->merge(collect($clusters)->pluck('deal_ids')->flatten())->unique();

        if ($deal_ids->isNotEmpty()) {
            $deals = CrmDeal::whereIn('id', $deal_ids)->get()->keyBy('id');
        }

        // сумма сделок кластера: каждая сделка один раз, сделки вне выгрузки — ноль (как у одиночного КП)
        foreach ($clusters as $group => $cluster) {
            $clusters[$group]['deals_total'] = (float) collect($cluster['deal_ids'])
                ->sum(fn($id) => (float) ($deals->get($id)?->opportunity ?? 0));
            $clusters[$group]['diff'] = $clusters[$group]['deals_total'] - $cluster['proposal_total'];
        }

        $rows = $proposals->map(fn($proposal) => static::check(
            $proposal,
            $links->get($proposal->group, collect()),
            $deals,
            $clusters[$proposal->group] ?? null
        ));

        if (!isset($params['only_issues']) || $params['only_issues']) {
            $rows = $rows->filter(fn($row) => !empty($row['issues']));
        }

        if (!empty($params['issue'])) {
            $rows = $rows->filter(fn($row) => in_array($params['issue'], $row['issue_codes'], true));
        }

        return $rows
            ->sortByDesc(fn($row) => count($row['issue_codes']))
            ->values();
    }

    /**
     * Кластеры КП с общими сделками (patch v43)
     *
     * Кластер — связная компонента графа КП↔сделки (ProposalDealService::cluster()).
     * Обход делается только для КП, у которых хотя бы одна сделка привязана ещё
     * к другому КП; у остальных кластер — само КП, и сверка идёт как раньше.
     *
     * «Ведущее» КП кластера — с наименьшим id последней редакции: на нём
     * считается расхождение в итогах, остальные КП кластера ссылаются на него.
     *
     * @param Collection $proposals Последние редакции КП выборки
     * @param Collection $dealIds Сделки этих КП
     * @return array [group => [
     *     'key' => string — ключ кластера (группы через запятую, по возрастанию),
     *     'groups' => [group, ...],
     *     'members' => [{group, number, name, url, total}, ...] — КП кластера, первым ведущее,
     *     'lead' => group ведущего КП,
     *     'deal_ids' => [int, ...] — уникальные сделки кластера,
     *     'truncated' => bool,
     *     'proposal_total' => сумма последних вариантов всех КП кластера,
     * ]] — только для КП, у которых кластер больше одного КП
     */
    protected static function clusters(Collection $proposals, Collection $dealIds): array
    {
        if ($dealIds->isEmpty()) return [];

        // группы, чьи сделки привязаны больше чем к одному КП
        $shared = ProposalCrmDeal::whereIn('crm_deal_id', $dealIds->all())
            ->get(['proposal_group', 'crm_deal_id'])
            ->groupBy('crm_deal_id')
            ->filter(fn($rows) => $rows->pluck('proposal_group')->unique()->count() > 1)
            ->flatten()
            ->pluck('proposal_group')
            ->unique()
            ->flip();

        $found = [];
        foreach ($proposals as $proposal) {
            $group = (string) $proposal->group;
            if (!isset($shared[$group]) || isset($found[$group])) continue;

            $cluster = ProposalDealService::cluster($group);
            if (count($cluster['groups']) < 2) continue;

            foreach ($cluster['groups'] as $item) {
                $found[$item] ??= $cluster;
            }
        }

        if (empty($found)) return [];

        // КП кластеров, которых нет в выборке (другой фильтр), — догружаем
        // toBase(): merge() у Eloquent-коллекции склеивает по id модели, а нужны ключи-группы
        $known = $proposals->keyBy('group')->toBase();
        $missing = array_diff(array_keys($found), $known->keys()->all());
        if (!empty($missing)) {
            $known = $known->merge(Proposal::query()
                ->latestIteration()
                ->whereIn('group', $missing)
                ->with('variants')
                ->get()
                ->keyBy('group')
                ->toBase());
        }

        $ret = [];
        foreach ($found as $group => $cluster) {
            $members = collect($cluster['groups'])
                ->map(fn($item) => $known->get($item))
                ->filter()
                ->sortBy('id')
                ->map(fn(Proposal $member) => [
                    'group' => (string) $member->group,
                    'number' => (string) ($member->number ?? ''),
                    'name' => (string) $member->name,
                    'url' => route('deal_card.index', $member),
                    'total' => (float) ($member->variants->sortBy('id')->last()->cost_total ?? 0),
                ])
                ->values();

            $groups = $cluster['groups'];
            sort($groups);

            $ret[$group] = [
                'key' => implode(',', $groups),
                'groups' => $cluster['groups'],
                'members' => $members->all(),
                'lead' => $members->first()['group'] ?? $group,
                'deal_ids' => $cluster['deal_ids'],
                'truncated' => $cluster['truncated'],
                'proposal_total' => (float) $members->sum('total'),
            ];
        }

        return $ret;
    }

    /**
     * Сверка одного КП
     *
     * patch v43: если сделка КП общая с другими КП ($cluster), сумма сверяется
     * по кластеру — сумма уникальных сделок кластера против суммы всех КП кластера.
     * Валюта, «нет в выгрузке», стадия, «нет сделки», «нет расчёта» — по своим
     * привязкам КП, как раньше.
     *
     * @param Proposal $proposal
     * @param Collection $links
     * @param Collection $deals
     * @param array|null $cluster Кластер КП (см. clusters()), null — КП ни с кем сделок не делит
     * @return array
     */
    public static function check(Proposal $proposal, Collection $links, Collection $deals, ?array $cluster = null): array
    {
        $variant = $proposal->variants->sortBy('id')->last();
        $currency = CurrencyService::slug($proposal->currency_slug);
        $total = (float) ($variant->cost_total ?? 0);
        $status = ProposalStatus::tryFrom((string) $proposal->status);

        $issues = [];
        $deals_total = 0.0;
        $shared = !empty($cluster);
        // у общего кластера сумма сверяется ниже целиком, а не по сделке
        $single = !$shared && $links->count() === 1;
        $tolerance = static::amountTolerance();

        $links = $links->map(function ($link) use ($deals, $currency, $total, $single, &$issues, &$deals_total, $status, $tolerance) {
            $deal = $deals->get($link->crm_deal_id);
            $link->deal = $deal;
            $link->error = null;

            if (empty($deal)) {
                $link->error = 'нет в выгрузке Битрикс24';
                $issues['missing'] = 'Сделка #' . $link->crm_deal_id . ' не найдена в выгрузке';
                return $link;
            }

            $deal_currency = CurrencyService::slug($deal->currency_id);
            $deals_total += (float) $deal->opportunity;

            if ($deal_currency !== $currency) {
                $link->error = 'валюта ' . $deal_currency . ', у КП ' . $currency;
                $issues['currency'] = 'Сделка #' . $link->crm_deal_id . ': валюта ' . $deal_currency
                    . ', а у КП ' . $currency;
            }

            if ($single && abs((float) $deal->opportunity - $total) > $tolerance) {
                $issues['amount'] = 'Сделка ' . tools()->cost_normalize(round((float) $deal->opportunity))
                    . ' против ' . tools()->cost_normalize(round($total)) . ' в КП';
            }

            // стадия сделки против статуса КП
            if ($status) {
                $semantic = (string) $deal->stage_semantic_id;

                if ($status === ProposalStatus::WON && $semantic === static::SEMANTIC_FAIL) {
                    $issues['stage'] = 'КП выиграно, а сделка #' . $link->crm_deal_id . ' провалена';
                }

                if ($status === ProposalStatus::LOST && $semantic === static::SEMANTIC_SUCCESS) {
                    $issues['stage'] = 'КП проиграно, а сделка #' . $link->crm_deal_id . ' успешна';
                }
            }

            return $link;
        });

        $diff = $deals_total - $total;
        $own_total = $total;
        $own_deals_total = $deals_total;

        if ($shared) {
            // patch v43: сумма уникальных сделок кластера против суммы всех его КП
            $total = $cluster['proposal_total'];
            $deals_total = $cluster['deals_total'];
            $diff = $cluster['diff'];

            if (abs($diff) > $tolerance) {
                $issues['amount'] = 'Сумма ' . tools()->num_rus(count($cluster['deal_ids']), ['сделок', 'сделки', 'сделок'], 1)
                    . ' ' . tools()->cost_normalize(round($deals_total))
                    . ' против ' . tools()->cost_normalize(round($total)) . ' в КП '
                    . collect($cluster['members'])->map(fn($row) => $row['number'] ?: $row['name'])->implode(' + ');
            }
        } elseif (!$single && $links->isNotEmpty() && abs($diff) > $tolerance) {
            $issues['amount'] = 'Сумма ' . $links->count() . ' сделок '
                . tools()->cost_normalize(round($deals_total))
                . ' против ' . tools()->cost_normalize(round($total)) . ' в КП';
        }

        if (empty($variant)) {
            $issues['no_variant'] = 'У КП нет вариантов расчёта';
        }

        if ($links->isEmpty() && in_array((string) $proposal->status, static::DEAL_REQUIRED, true)) {
            $issues['no_deal'] = 'Статус «' . ($status?->data()['label'] ?? $proposal->status)
                . '», а сделка не привязана';
        }

        return [
            'proposal' => $proposal,
            'variant' => $variant,
            'currency' => $currency,
            'status' => $status,
            'links' => $links,
            'proposal_total' => $total,
            'deals_total' => $deals_total,
            'diff' => $diff,
            'issues' => $issues,
            'issue_codes' => array_keys($issues),
            // patch v43: у общей сделки proposal_total / deals_total / diff — суммы кластера,
            // own_* — только этого КП и его сделок
            'own_total' => $own_total,
            'own_deals_total' => $own_deals_total,
            'cluster' => [
                'shared' => $shared,
                'key' => $shared ? $cluster['key'] : (string) $proposal->group,
                // ведущее КП кластера: расхождение в итогах считается на нём
                'lead' => !$shared || $cluster['lead'] === (string) $proposal->group,
                'lead_number' => $shared ? static::leadLabel($cluster) : null,
                'others' => $shared
                    ? array_values(array_filter($cluster['members'], fn($row) => $row['group'] !== (string) $proposal->group))
                    : [],
                'deal_ids' => $shared ? $cluster['deal_ids'] : [],
                'truncated' => $shared && $cluster['truncated'],
            ],
        ];
    }

    /**
     * Номер (или название) ведущего КП кластера
     *
     * @param array $cluster
     * @return string
     */
    protected static function leadLabel(array $cluster): string
    {
        $lead = collect($cluster['members'])->firstWhere('group', $cluster['lead']);

        return (string) (($lead['number'] ?? '') ?: ($lead['name'] ?? ''));
    }

    /**
     * Сколько КП с каждым видом расхождения
     *
     * @param Collection $rows
     * @return array
     */
    public static function counters(Collection $rows): array
    {
        $ret = [];

        foreach (static::issues() as $code => $issue) {
            $ret[$code] = $rows->filter(fn($row) => in_array($code, $row['issue_codes'], true))->count();
        }

        return $ret;
    }

    /**
     * Деньги, по которым расходятся портал и CRM
     *
     * patch v43: суммы берутся по кластерам — у КП с общей сделкой в строке уже
     * суммы всего кластера, поэтому каждый кластер (и каждая его сделка) входит
     * в итог один раз, сколько бы его КП ни попало в выборку. count — по-прежнему
     * число КП с расхождением суммы, clusters — число расхождений без повторов.
     *
     * @param Collection $rows
     * @return array ['count', 'clusters', 'proposal_total', 'deals_total', 'diff']
     */
    public static function money(Collection $rows): array
    {
        $amount = $rows->filter(fn($row) => in_array('amount', $row['issue_codes'], true));
        $unique = $amount->unique(fn($row) => $row['cluster']['key'] ?? $row['proposal']->group);

        return [
            'count' => $amount->count(),
            'clusters' => $unique->count(),
            'proposal_total' => (float) $unique->sum('proposal_total'),
            'deals_total' => (float) $unique->sum('deals_total'),
            'diff' => (float) $unique->sum('diff'),
        ];
    }
}
