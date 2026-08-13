<?php

namespace App\Modules\Pub\Analytics\Services;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Скоринг партнёров.
 *
 * Партнёра видно по четырём вещам: доводит ли он КП до сделки, на какие деньги,
 * платят ли по его договорам вовремя и сколько он просит скидки. Сервис считает
 * это по всей истории и сводит в один балл.
 *
 * Балл — не оценка человека, а способ отсортировать список: у кого смотреть
 * дела в первую очередь.
 *
 * Суммы приводятся к рублям по текущему курсу: у партнёров КП в разных валютах,
 * иначе сравнивать нечего.
 */
class PartnerScoringService
{
    /** Веса составляющих балла */
    public const WEIGHT_CONVERSION = 0.40;
    public const WEIGHT_VOLUME = 0.25;
    public const WEIGHT_PAYMENT = 0.25;
    public const WEIGHT_DISCOUNT = 0.10;

    /** Скидка партнёру, выше которой штраф максимальный */
    public const DISCOUNT_CEILING_P = 30;

    /**
     * Партнёры с показателями
     *
     * @param array $params ['year' => int|null, 'grade' => string|null, 'q' => string|null]
     * @return Collection
     */
    public static function rows(array $params = []): Collection
    {
        $discounts = DiscountAnalysisService::rows([
            'year' => $params['year'] ?? null,
        ])->groupBy(fn($row) => (int) ($row['partner']->id ?? 0));

        $builder = Proposal::query()->latestIteration()->with(['partner', 'variants']);

        if (!empty($params['year'])) {
            $builder->whereYear('sended_at', (int) $params['year']);
        }

        $proposals = $builder->get()->filter(fn($proposal) => !empty($proposal->partner));
        if ($proposals->isEmpty()) return collect();

        $contracts = static::contracts($proposals->pluck('id'));
        $payments = static::payments($contracts->pluck('id'));

        $rows = $proposals
            ->groupBy(fn($proposal) => (int) $proposal->partner->id)
            ->map(fn($group, $partner_id) => static::partner(
                $group,
                $contracts->where('partner_id', $partner_id),
                $payments,
                $discounts->get((int) $partner_id, collect())
            ))
            ->values();

        if (!empty($params['grade'])) {
            $rows = $rows->filter(fn($row) => $row['grade_key'] === $params['grade'])->values();
        }

        if (!empty($params['q'])) {
            $like = mb_strtolower(trim($params['q']));
            $rows = $rows->filter(fn($row) => str_contains(mb_strtolower((string) $row['partner']->name), $like))->values();
        }

        return static::score($rows)->sortByDesc('score')->values();
    }

    /**
     * Показатели одного партнёра
     *
     * @return array
     */
    public static function partner(Collection $proposals, Collection $contracts, Collection $payments, Collection $discounts): array
    {
        $partner = $proposals->first()->partner;

        $won = $proposals->filter(fn($proposal) => (string) $proposal->status === ProposalStatus::WON->value);
        $lost = $proposals->filter(fn($proposal) => (string) $proposal->status === ProposalStatus::LOST->value);
        $decided = $won->count() + $lost->count();

        // сумма выигранных КП по последнему созданному варианту, в рублях
        $amount_won = 0.0;
        foreach ($won as $proposal) {
            $variant = $proposal->variants->sortBy('id')->last();
            $rate = CurrencyService::getConvertRateForDate(now(), $proposal->currency_slug, null) ?? 1;
            $amount_won += (float) ($variant->cost_total ?? 0) * $rate;
        }

        $signed = $contracts->where('cb_signed', 1);
        $spec_ids = $contracts->pluck('id');
        $rows = $payments->whereIn('contract_id', $spec_ids);

        $paid = $rows->where('state', 'paid');
        $overdue = $rows->where('state', 'overdue');
        $paid_sum = (float) $paid->sum('amount_fact');
        $overdue_sum = (float) $overdue->sum('amount_plan');

        // средний срок от отправки КП до подписания договора
        $days = collect();
        foreach ($signed as $contract) {
            $proposal = $proposals->firstWhere('id', $contract->proposal_id);
            if (empty($proposal?->sended_at) || empty($contract->date)) continue;

            $diff = Carbon::parse($proposal->sended_at)->diffInDays($contract->date, false);
            if ($diff >= 0) $days->push($diff);
        }

        $discount_list = (float) $discounts->sum('list');
        $discount_partner = (float) $discounts->sum('partner_amount');
        $discount_customer = (float) $discounts->sum('customer');

        return [
            'partner' => $partner,
            'grade' => PartnerGrade::tryFrom((string) $partner->grade)?->data(),
            'grade_key' => (string) $partner->grade,
            'proposals' => $proposals->count(),
            'won' => $won->count(),
            'lost' => $lost->count(),
            'in_work' => $proposals->count() - $decided,
            'conversion' => $decided > 0 ? $won->count() / $decided * 100 : 0,
            'amount_won' => $amount_won,
            'contracts' => $contracts->count(),
            'contracts_signed' => $signed->count(),
            'payments_paid' => $paid->count(),
            'payments_overdue' => $overdue->count(),
            'paid_sum' => $paid_sum,
            'overdue_sum' => $overdue_sum,
            'payment_discipline' => ($paid->count() + $overdue->count()) > 0
                ? $paid->count() / ($paid->count() + $overdue->count()) * 100
                : null,
            'days_to_contract' => $days->isNotEmpty() ? (int) round($days->avg()) : null,
            'discount_partner_p' => ($discount_list - $discount_customer) > 0
                ? $discount_partner / ($discount_list - $discount_customer) * 100
                : 0,
            'discount_customer_p' => $discount_list > 0 ? $discount_customer / $discount_list * 100 : 0,
        ];
    }

    /**
     * Балл 0–100.
     *
     * Конверсия 40 + объём 25 + платёжная дисциплина 25 − штраф за скидку 10.
     * Объём нормируется на лучшего в выборке: абсолютных рублёвых порогов,
     * одинаково честных для всех партнёров, не бывает.
     *
     * @param Collection $rows
     * @return Collection
     */
    public static function score(Collection $rows): Collection
    {
        $best = (float) $rows->max('amount_won');

        return $rows->map(function ($row) use ($best) {
            $volume = $best > 0 ? $row['amount_won'] / $best * 100 : 0;

            // партнёру без платежей дисциплину не ставим — считаем нейтрально
            $payment = $row['payment_discipline'] ?? 50;

            $penalty = min(100, $row['discount_partner_p'] / static::DISCOUNT_CEILING_P * 100);

            $score = static::WEIGHT_CONVERSION * $row['conversion']
                + static::WEIGHT_VOLUME * $volume
                + static::WEIGHT_PAYMENT * $payment
                - static::WEIGHT_DISCOUNT * $penalty;

            $row['volume_score'] = $volume;
            $row['score'] = max(0, min(100, round($score)));
            $row['rank'] = static::rank($row['score']);

            return $row;
        });
    }

    /**
     * Буква и цвет по баллу
     *
     * @param float $score
     * @return array
     */
    public static function rank(float $score): array
    {
        return match (true) {
            $score >= 80 => ['letter' => 'A', 'color' => 'success', 'label' => 'Опора'],
            $score >= 65 => ['letter' => 'B', 'color' => 'primary', 'label' => 'Надёжный'],
            $score >= 50 => ['letter' => 'C', 'color' => 'info', 'label' => 'Рабочий'],
            $score >= 35 => ['letter' => 'D', 'color' => 'warning', 'label' => 'Слабый'],
            default => ['letter' => 'E', 'color' => 'danger', 'label' => 'Требует внимания'],
        };
    }

    /**
     * Договоры по КП выборки
     *
     * @param Collection $proposalIds
     * @return Collection
     */
    public static function contracts(Collection $proposalIds): Collection
    {
        if ($proposalIds->isEmpty()) return collect();

        return collect(DB::table('contracts')
            ->whereIn('proposal_id', $proposalIds)
            ->select(['id', 'number', 'date', 'cb_signed', 'proposal_id', 'partner_id', 'company_id', 'old'])
            ->get())
            ->map(function ($row) {
                $row->date = $row->date ? Carbon::parse($row->date) : null;
                return $row;
            });
    }

    /**
     * Платежи по договорам выборки.
     * Отменённые спецификации просрочки не дают.
     *
     * @param Collection $contractIds
     * @return Collection
     */
    public static function payments(Collection $contractIds): Collection
    {
        if ($contractIds->isEmpty()) return collect();

        return collect(DB::table('payments as p')
            ->join('contract_specifications as s', 's.id', '=', 'p.contract_specification_id')
            ->whereIn('s.contract_id', $contractIds)
            ->select([
                'p.id', 'p.date_plan', 'p.date_fact', 'p.amount_plan', 'p.amount_fact',
                'p.is_unknown', 's.contract_id', 's.status as spec_status',
            ])
            ->get())
            ->map(function ($row) {
                $row->date_plan = $row->date_plan ? Carbon::parse($row->date_plan) : null;
                $row->date_fact = $row->date_fact ? Carbon::parse($row->date_fact) : null;
                $row->is_canceled = strtolower((string) $row->spec_status) === 'canceled';

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
     * Итоги по выборке
     *
     * @param Collection $rows
     * @return array
     */
    public static function totals(Collection $rows): array
    {
        return [
            'count' => $rows->count(),
            'proposals' => (int) $rows->sum('proposals'),
            'won' => (int) $rows->sum('won'),
            'amount_won' => (float) $rows->sum('amount_won'),
            'overdue' => (int) $rows->sum('payments_overdue'),
            'score' => $rows->isNotEmpty() ? round($rows->avg('score')) : 0,
        ];
    }
}
