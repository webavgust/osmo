<?php

namespace App\Modules\Pub\DealCard\Services;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Сквозная карточка сделки.
 *
 * Цепочка КП → сделка Битрикса → договор → спецификация → платежи → лицензии
 * лежит в четырёх разделах портала. Сервис собирает её целиком и показывает,
 * на каком шаге всё остановилось.
 */
class DealChainService
{
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
        $payments = static::payments($specs->pluck('id'));
        $keys = static::licenseKeys($specs->pluck('id'));

        $deal = $last->crm_deal_id ? CrmDeal::find($last->crm_deal_id) : null;

        return [
            'proposal' => $last,
            'iterations' => $iterations,
            'deal' => $deal,
            'contracts' => $contracts,
            'specifications' => $specs,
            'payments' => $payments,
            'license_keys' => $keys,
            'steps' => static::steps($last, $deal, $contracts, $specs, $payments, $keys),
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
                'c.currency_slug', 'c.proposal_id', 'c.old',
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
                'co.name as company_name',
            ])
            ->orderBy('s.id')
            ->get())
            ->map(function ($row) {
                $row->closed_at = $row->closed_at ? Carbon::parse($row->closed_at) : null;
                return $row;
            });
    }

    /**
     * Платежи спецификаций
     *
     * @param \Illuminate\Support\Collection $specIds
     * @return \Illuminate\Support\Collection
     */
    public static function payments($specIds)
    {
        if ($specIds->isEmpty()) return collect();

        return collect(DB::table('payments')
            ->whereIn('contract_specification_id', $specIds)
            ->orderByRaw('COALESCE(date_plan, date_fact)')
            ->get())
            ->map(function ($row) {
                $row->date_plan = $row->date_plan ? Carbon::parse($row->date_plan) : null;
                $row->date_fact = $row->date_fact ? Carbon::parse($row->date_fact) : null;

                $row->state = match (true) {
                    !empty($row->date_fact) => 'paid',
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
     * ok — шаг пройден, warn — есть данные, но что-то не завершено,
     * empty — шага нет (здесь всё и встало).
     *
     * @return array
     */
    public static function steps($proposal, $deal, $contracts, $specs, $payments, $keys): array
    {
        $signed_contracts = $contracts->where('cb_signed', 1);
        $signed_specs = $specs->where('is_signed', 1);
        $paid = $payments->where('state', 'paid');
        $overdue = $payments->where('state', 'overdue');

        $steps = [
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
                'title' => 'Сделка Битрикс24',
                'icon' => 'fa-handshake',
                'state' => $deal ? 'ok' : 'empty',
                'value' => $deal ? '#' . $deal->id . ' ' . $deal->title : 'Не привязана',
                'hint' => $deal
                    ? trim((string) $deal->stage_name)
                    : 'Привяжите сделку — без неё не сойдётся сверка с CRM',
                'url' => null,
            ],
            [
                'code' => 'contract',
                'title' => 'Договор',
                'icon' => 'fa-file-signature',
                'state' => match (true) {
                    $contracts->isEmpty() => 'empty',
                    $signed_contracts->isEmpty() => 'warn',
                    default => 'ok',
                },
                'value' => $contracts->isEmpty()
                    ? 'Нет договора'
                    : $contracts->count() . ' шт, подписано ' . $signed_contracts->count(),
                'hint' => $contracts->isEmpty()
                    ? 'КП есть, договора нет'
                    : $contracts->pluck('number')->filter()->implode(', '),
                'url' => null,
            ],
            [
                'code' => 'specification',
                'title' => 'Спецификации',
                'icon' => 'fa-list-check',
                'state' => match (true) {
                    $specs->isEmpty() => 'empty',
                    $signed_specs->isEmpty() => 'warn',
                    default => 'ok',
                },
                'value' => $specs->isEmpty()
                    ? 'Нет спецификаций'
                    : $specs->count() . ' шт, подписано ' . $signed_specs->count(),
                'hint' => $specs->isEmpty()
                    ? 'Договор есть, спецификация не оформлена'
                    : 'Сумма: ' . tools()->cost_normalize(round((float) $specs->sum('amount'))),
                'url' => null,
            ],
            [
                'code' => 'payment',
                'title' => 'Платежи',
                'icon' => 'fa-ruble-sign',
                'state' => match (true) {
                    $payments->isEmpty() => 'empty',
                    $overdue->isNotEmpty() => 'warn',
                    $paid->count() === $payments->count() => 'ok',
                    default => 'warn',
                },
                'value' => $payments->isEmpty()
                    ? 'Нет платежей'
                    : 'Оплачено ' . $paid->count() . ' из ' . $payments->count(),
                'hint' => match (true) {
                    $payments->isEmpty() => 'Спецификация есть, платежей нет',
                    $overdue->isNotEmpty() => 'Просрочено платежей: ' . $overdue->count(),
                    default => 'Просрочки нет',
                },
                'url' => route('payment_calendar.index'),
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

        return $steps;
    }

    /**
     * Деньги по цепочке
     *
     * @return array
     */
    public static function money($specs, $payments): array
    {
        $spec_sum = (float) $specs->sum('amount');
        $plan = (float) $payments->sum('amount_plan');
        $fact = (float) $payments->sum('amount_fact');

        return [
            'spec' => $spec_sum,
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
