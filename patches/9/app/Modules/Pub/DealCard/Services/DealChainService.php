<?php

namespace App\Modules\Pub\DealCard\Services;

use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Сквозная карточка сделки.
 *
 * Цепочка КП → сделки Битрикса → договоры → спецификации → платежи → лицензии
 * лежит в четырёх разделах портала. Сервис собирает её целиком и показывает,
 * на каком шаге всё остановилось.
 *
 * Шаг считается пройденным (зелёным), только когда пройден полностью:
 * подписаны все договоры, подписаны все действующие спецификации.
 * Отменённые спецификации из расчёта выпадают — по ним никто ничего не ждёт.
 */
class DealChainService
{
    /** Статус отменённой спецификации */
    public const SPEC_CANCELED = 'canceled';

    /**
     * Вся цепочка по группе КП
     *
     * @param Proposal $proposal Любая итерация КП
     * @return array
     */
    public static function build(Proposal $proposal): array
    {
        $iterations = Proposal::where('group', $proposal->group)
            ->orderBy('iteration')
            ->get();

        $last = $iterations->last() ?? $proposal;

        $contracts = static::contracts($iterations->pluck('id'));
        $specs = static::specifications($contracts->pluck('id'));
        $payments = static::payments($specs);
        $keys = static::licenseKeys($specs->pluck('id'));

        // у КП может быть несколько сделок Битрикса
        $links = ProposalDealService::links($last);

        return [
            'proposal' => $last,
            'iterations' => $iterations,
            'deal_links' => $links,
            'deals' => $links->map(fn($link) => $link->deal)->filter()->values(),
            'contracts' => $contracts,
            'specifications' => $specs,
            'payments' => $payments,
            'license_keys' => $keys,
            'steps' => static::steps($last, $links, $contracts, $specs, $payments, $keys),
            'money' => static::money($specs, $payments),
        ];
    }

    /**
     * Договоры по всем итерациям КП
     *
     * @param \Illuminate\Support\Collection $proposalIds
     * @return \Illuminate\Support\Collection
     */
    public static function contracts($proposalIds)
    {
        if ($proposalIds->isEmpty()) return collect();

        return collect(DB::table('contracts as c')
            ->leftJoin('companies as co', 'co.id', '=', 'c.company_id')
            ->leftJoin('partners as pa', 'pa.id', '=', 'c.partner_id')
            ->whereIn('c.proposal_id', $proposalIds)
            ->select([
                'c.id', 'c.number', 'c.date', 'c.type', 'c.cb_signed',
                'c.currency_slug', 'c.proposal_id', 'c.old', 'c.company_id',
                'co.name as company_name', 'pa.name as partner_name',
            ])
            ->orderBy('c.date')
            ->get())
            ->map(function ($row) {
                $row->date = $row->date ? Carbon::parse($row->date) : null;
                return $row;
            });
    }

    /**
     * Спецификации договоров
     *
     * @param \Illuminate\Support\Collection $contractIds
     * @return \Illuminate\Support\Collection
     */
    public static function specifications($contractIds)
    {
        if ($contractIds->isEmpty()) return collect();

        return collect(DB::table('contract_specifications as s')
            ->leftJoin('companies as co', 'co.id', '=', 's.company_id')
            ->whereIn('s.contract_id', $contractIds)
            ->select([
                's.id', 's.name', 's.amount', 's.currency_slug', 's.is_signed',
                's.status', 's.closed_at', 's.contract_id', 's.project_configuration_id',
                's.company_id', 'co.name as company_name',
            ])
            ->orderBy('s.id')
            ->get())
            ->map(function ($row) {
                $row->closed_at = $row->closed_at ? Carbon::parse($row->closed_at) : null;
                $row->is_canceled = strtolower((string) $row->status) === static::SPEC_CANCELED;
                return $row;
            });
    }

    /**
     * Платежи спецификаций.
     * Платежи отменённых спецификаций не дают просрочки.
     *
     * @param \Illuminate\Support\Collection $specs
     * @return \Illuminate\Support\Collection
     */
    public static function payments($specs)
    {
        if ($specs->isEmpty()) return collect();

        $canceled = $specs->where('is_canceled', true)->pluck('id')->all();

        return collect(DB::table('payments')
            ->whereIn('contract_specification_id', $specs->pluck('id'))
            ->orderByRaw('COALESCE(date_plan, date_fact)')
            ->get())
            ->map(function ($row) use ($canceled) {
                $row->date_plan = $row->date_plan ? Carbon::parse($row->date_plan) : null;
                $row->date_fact = $row->date_fact ? Carbon::parse($row->date_fact) : null;
                $row->is_canceled = in_array($row->contract_specification_id, $canceled);

                $row->state = match (true) {
                    !empty($row->date_fact) => 'paid',
                    $row->is_canceled => 'canceled',
                    !empty($row->is_unknown) || empty($row->date_plan) => 'unknown',
                    $row->date_plan->isPast() => 'overdue',
                    default => 'planned',
                };

                return $row;
            });
    }

    /**
     * Лицензионные ключи спецификаций
     *
     * @param \Illuminate\Support\Collection $specIds
     * @return \Illuminate\Support\Collection
     */
    public static function licenseKeys($specIds)
    {
        if ($specIds->isEmpty()) return collect();

        return collect(DB::table('license_keys')
            ->whereIn('contract_specification_id', $specIds)
            ->orderBy('active_to')
            ->get())
            ->map(function ($row) {
                $row->active_from = $row->active_from ? Carbon::parse($row->active_from) : null;
                $row->active_to = $row->active_to ? Carbon::parse($row->active_to) : null;
                $row->days_left = $row->active_to
                    ? (int) now()->startOfDay()->diffInDays($row->active_to, false)
                    : null;
                return $row;
            });
    }

    /**
     * Шаги цепочки с состоянием
     *
     * ok — шаг пройден полностью, warn — есть данные, но не завершено,
     * empty — шага нет (здесь всё и встало).
     *
     * @return array
     */
    public static function steps($proposal, $links, $contracts, $specs, $payments, $keys): array
    {
        // архивные договоры в оценке не участвуют
        $live_contracts = $contracts->where('old', '!=', 1);
        $signed_contracts = $live_contracts->where('cb_signed', 1);
        $unsigned_contracts = $live_contracts->where('cb_signed', '!=', 1);

        // отменённые спецификации в оценке не участвуют
        $live_specs = $specs->where('is_canceled', false);
        $canceled_specs = $specs->where('is_canceled', true);
        $signed_specs = $live_specs->where('is_signed', 1);
        $unsigned_specs = $live_specs->where('is_signed', '!=', 1);

        $live_payments = $payments->where('state', '!=', 'canceled');
        $paid = $live_payments->where('state', 'paid');
        $overdue = $live_payments->where('state', 'overdue');

        $deals = $links->map(fn($link) => $link->deal)->filter();
        $main = $links->first();

        return [
            [
                'code' => 'proposal',
                'title' => 'Коммерческое предложение',
                'icon' => 'fa-file-invoice',
                'state' => 'ok',
                'value' => $proposal->name_number ?? $proposal->name,
                'hint' => 'Итераций: ' . ($proposal->iteration ?? 1)
                    . ' · статус: ' . ($proposal->status_decorate['label'] ?? '—'),
                'url' => route('proposal.detail', [$proposal, $proposal->iteration]),
            ],
            [
                'code' => 'deal',
                'title' => 'Сделки Битрикс24',
                'icon' => 'fa-handshake',
                'state' => $links->isEmpty() ? 'empty' : 'ok',
                'value' => match (true) {
                    $links->isEmpty() => 'Не привязана',
                    $links->count() === 1 => '#' . $main->crm_deal_id . ' ' . ($main->deal->title ?? ''),
                    default => $links->count() . ' сделки',
                },
                'hint' => match (true) {
                    $links->isEmpty() => 'Привяжите сделку — без неё не сойдётся сверка с CRM',
                    $links->count() === 1 => trim((string) ($main->deal->stage_name ?? 'Нет в выгрузке Битрикса')),
                    default => 'Главная #' . $main->crm_deal_id . ' · остальные: '
                        . $links->skip(1)->map(fn($link) => '#' . $link->crm_deal_id)->implode(', '),
                },
                'url' => null,
            ],
            [
                'code' => 'contract',
                'title' => 'Договоры',
                'icon' => 'fa-file-signature',
                'state' => match (true) {
                    $live_contracts->isEmpty() => 'empty',
                    $unsigned_contracts->isNotEmpty() => 'warn',
                    default => 'ok',
                },
                'value' => $live_contracts->isEmpty()
                    ? 'Нет договора'
                    : 'Подписано ' . $signed_contracts->count() . ' из ' . $live_contracts->count(),
                'hint' => match (true) {
                    $live_contracts->isEmpty() => 'КП есть, договора нет',
                    $unsigned_contracts->isNotEmpty() => 'Не подписаны: '
                        . $unsigned_contracts->map(fn($row) => $row->number ?: '№ не указан')->implode(', '),
                    default => $live_contracts->pluck('number')->filter()->implode(', '),
                },
                'url' => null,
            ],
            [
                'code' => 'specification',
                'title' => 'Спецификации',
                'icon' => 'fa-list-check',
                'state' => match (true) {
                    $live_specs->isEmpty() => 'empty',
                    $unsigned_specs->isNotEmpty() => 'warn',
                    default => 'ok',
                },
                'value' => $live_specs->isEmpty()
                    ? 'Нет спецификаций'
                    : 'Подписано ' . $signed_specs->count() . ' из ' . $live_specs->count(),
                'hint' => match (true) {
                    $live_specs->isEmpty() && $canceled_specs->isNotEmpty() =>
                        'Все спецификации отменены (' . $canceled_specs->count() . ')',
                    $live_specs->isEmpty() => 'Договор есть, спецификация не оформлена',
                    $unsigned_specs->isNotEmpty() => 'Не подписаны: '
                        . $unsigned_specs->map(fn($row) => $row->name ?: 'без названия')->take(3)->implode(', ')
                        . ($canceled_specs->isNotEmpty() ? ' · отменено: ' . $canceled_specs->count() : ''),
                    default => 'Сумма: ' . tools()->cost_normalize(round((float) $live_specs->sum('amount')))
                        . ($canceled_specs->isNotEmpty() ? ' · отменено: ' . $canceled_specs->count() : ''),
                },
                'url' => null,
            ],
            [
                'code' => 'payment',
                'title' => 'Платежи',
                'icon' => 'fa-ruble-sign',
                'state' => match (true) {
                    $live_payments->isEmpty() => 'empty',
                    $overdue->isNotEmpty() => 'warn',
                    $paid->count() === $live_payments->count() => 'ok',
                    default => 'warn',
                },
                'value' => $live_payments->isEmpty()
                    ? 'Нет платежей'
                    : 'Оплачено ' . $paid->count() . ' из ' . $live_payments->count(),
                'hint' => match (true) {
                    $live_payments->isEmpty() => 'Спецификация есть, платежей нет',
                    $overdue->isNotEmpty() => 'Просрочено платежей: ' . $overdue->count(),
                    default => 'Просрочки нет',
                },
                'url' => route('payment_calendar.index', ['q' => $proposal->number]),
            ],
            [
                'code' => 'license',
                'title' => 'Лицензии',
                'icon' => 'fa-key',
                'state' => match (true) {
                    $keys->isEmpty() => 'empty',
                    $keys->where('days_left', '<', 0)->isNotEmpty() => 'warn',
                    default => 'ok',
                },
                'value' => $keys->isEmpty() ? 'Ключей нет' : $keys->count() . ' ключ(ей)',
                'hint' => $keys->isEmpty()
                    ? 'Лицензии по этой сделке не выдавались'
                    : 'Ближайшее окончание: ' . ($keys->first()?->active_to?->format('d.m.Y') ?? '—'),
                'url' => null,
            ],
        ];
    }

    /**
     * Деньги по цепочке. Отменённые спецификации считаем отдельно.
     *
     * @return array
     */
    public static function money($specs, $payments): array
    {
        $live_specs = $specs->where('is_canceled', false);
        $live_payments = $payments->where('state', '!=', 'canceled');

        $spec_sum = (float) $live_specs->sum('amount');
        $plan = (float) $live_payments->sum('amount_plan');
        $fact = (float) $live_payments->sum('amount_fact');

        return [
            'spec' => $spec_sum,
            'canceled' => (float) $specs->where('is_canceled', true)->sum('amount'),
            'plan' => $plan,
            'fact' => $fact,
            'left' => max(0, $plan - $fact),
            'progress' => $plan > 0 ? min(100, round($fact / $plan * 100)) : 0,
            // расхождение суммы спецификаций и плана платежей — повод проверить
            'mismatch' => $spec_sum > 0 && abs($spec_sum - $plan) > 1,
        ];
    }

    /**
     * Первый незавершённый шаг — где встала сделка
     *
     * @param array $steps
     * @return array|null
     */
    public static function bottleneck(array $steps): ?array
    {
        foreach ($steps as $step) {
            if ($step['state'] !== 'ok') return $step;
        }

        return null;
    }
}
