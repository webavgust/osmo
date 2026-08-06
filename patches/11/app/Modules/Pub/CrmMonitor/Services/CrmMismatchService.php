<?php

namespace App\Modules\Pub\CrmMonitor\Services;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
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
 */
class CrmMismatchService
{
    /** Допустимое расхождение сумм, в единицах валюты */
    public const AMOUNT_TOLERANCE = 1;

    /** Статусы, для которых сделка в Битриксе обязательна */
    public const DEAL_REQUIRED = ['sent', 'negotiation', 'won'];

    /** Стадии Битрикса: успех / провал */
    public const SEMANTIC_SUCCESS = 'S';
    public const SEMANTIC_FAIL = 'F';

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
                'hint' => 'Сумма сделок в Битриксе отличается от последнего варианта КП',
            ],
            'currency' => [
                'label' => 'Разная валюта',
                'color' => 'danger',
                'icon' => 'fa-coins',
                'hint' => 'Валюта сделки не совпадает с валютой КП',
            ],
            'missing' => [
                'label' => 'Сделки нет в Битриксе',
                'color' => 'dark',
                'icon' => 'fa-link-slash',
                'hint' => 'Привязка есть, но такой сделки нет в выгрузке: её удалили или не синхронизировали',
            ],
            'no_deal' => [
                'label' => 'Сделка не привязана',
                'color' => 'warning',
                'icon' => 'fa-unlink',
                'hint' => 'КП отправлено или выиграно, но сделки Битрикса у него нет',
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
        if ($deal_ids->isNotEmpty()) {
            $deals = CrmDeal::whereIn('id', $deal_ids)->get()->keyBy('id');
        }

        $rows = $proposals->map(fn($proposal) => static::check(
            $proposal,
            $links->get($proposal->group, collect()),
            $deals
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
     * Сверка одного КП
     *
     * @param Proposal $proposal
     * @param Collection $links
     * @param Collection $deals
     * @return array
     */
    public static function check(Proposal $proposal, Collection $links, Collection $deals): array
    {
        $variant = $proposal->variants->sortBy('id')->last();
        $currency = CurrencyService::slug($proposal->currency_slug);
        $total = (float) ($variant->cost_total ?? 0);
        $status = ProposalStatus::tryFrom((string) $proposal->status);

        $issues = [];
        $deals_total = 0.0;
        $single = $links->count() === 1;

        $links = $links->map(function ($link) use ($deals, $currency, $total, $single, &$issues, &$deals_total, $status) {
            $deal = $deals->get($link->crm_deal_id);
            $link->deal = $deal;
            $link->error = null;

            if (empty($deal)) {
                $link->error = 'нет в выгрузке Битрикса';
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

            if ($single && abs((float) $deal->opportunity - $total) > static::AMOUNT_TOLERANCE) {
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

        if (!$single && $links->isNotEmpty() && abs($diff) > static::AMOUNT_TOLERANCE) {
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
        ];
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
     * @param Collection $rows
     * @return array
     */
    public static function money(Collection $rows): array
    {
        $amount = $rows->filter(fn($row) => in_array('amount', $row['issue_codes'], true));

        return [
            'count' => $amount->count(),
            'proposal_total' => (float) $amount->sum('proposal_total'),
            'deals_total' => (float) $amount->sum('deals_total'),
            'diff' => (float) $amount->sum('diff'),
        ];
    }
}
