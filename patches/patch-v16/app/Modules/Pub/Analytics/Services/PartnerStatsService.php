<?php

namespace App\Modules\Pub\Analytics\Services;

use App\Modules\Pub\Proposal\Models\ProposalStatus;
use Illuminate\Support\Collection;

/**
 * Детальная статистика по одному партнёру — то, что раскрывается из строки
 * скоринга: разбивка по годам и расшифровка каждой цифры.
 *
 * Считает поверх тех же выборок, что и скоринг, поэтому цифры в попапе и в
 * таблице сходятся по определению. Текущий год — на сегодняшний момент,
 * он всегда неполный.
 */
class PartnerStatsService
{
    /**
     * Разбивка по годам: показатели, балл и место в рейтинге
     *
     * @param int $partner_id
     * @return Collection
     */
    public static function years(int $partner_id): Collection
    {
        $years = static::partnerYears($partner_id);

        return collect($years)->map(function ($year) use ($partner_id) {
            $rows = PartnerScoringService::ranked($year);
            $row = $rows->first(fn($row) => (int) $row['partner']->id === $partner_id);

            return [
                'year' => $year,
                'current' => $year === (int) now()->year,
                'place' => $row['place'] ?? null,
                'total' => $rows->count(),
                'score' => $row['score'] ?? null,
                'rank' => $row['rank'] ?? null,
                'row' => $row,
            ];
        })->values();
    }

    /**
     * Годы, в которых у партнёра хоть что-то было
     *
     * @param int $partner_id
     * @return array
     */
    public static function partnerYears(int $partner_id): array
    {
        $years = collect();

        PartnerScoringService::proposals()
            ->where('partner_id', $partner_id)
            ->each(fn($row) => $years->push($row->sended_at?->year));

        PartnerScoringService::specifications()
            ->where('partner_id', $partner_id)
            ->each(fn($row) => $years->push(PartnerScoringService::specYear($row)));

        return $years->filter()->unique()->sortDesc()->values()->all();
    }

    /**
     * Выигранные КП — расшифровка колонки «Объём»
     *
     * @param int $partner_id
     * @param int|null $year
     * @return Collection
     */
    public static function volume(int $partner_id, ?int $year = null): Collection
    {
        return PartnerScoringService::proposals()
            ->where('partner_id', $partner_id)
            ->filter(fn($row) => (string) $row->status === ProposalStatus::WON->value)
            ->filter(fn($row) => !$year || $row->sended_at?->year === $year)
            ->sortByDesc(fn($row) => $row->sended_at)
            ->values();
    }

    /**
     * Спецификации по договорам — расшифровка колонки «Договор»
     *
     * @param int $partner_id
     * @param int|null $year
     * @return Collection сгруппировано по номеру договора
     */
    public static function contracts(int $partner_id, ?int $year = null): Collection
    {
        return PartnerScoringService::specifications()
            ->where('partner_id', $partner_id)
            ->filter(fn($row) => !$year || PartnerScoringService::specYear($row) === $year)
            ->sortByDesc(fn($row) => $row->attached_at ?? $row->contract_date)
            ->groupBy('contract_id');
    }

    /**
     * Платежи — расшифровка колонки «Платежи»
     *
     * @param int $partner_id
     * @param int|null $year
     * @return Collection
     */
    public static function payments(int $partner_id, ?int $year = null): Collection
    {
        return PartnerScoringService::payments()
            ->where('partner_id', $partner_id)
            ->filter(fn($row) => !$year || ($row->date_fact ?? $row->date_plan)?->year === $year)
            ->sortByDesc(fn($row) => $row->date_fact ?? $row->date_plan)
            ->values();
    }

    /**
     * Привязки КП к спецификациям — расшифровка колонки «КП → договор»
     *
     * @param int $partner_id
     * @param int|null $year
     * @return Collection
     */
    public static function links(int $partner_id, ?int $year = null): Collection
    {
        return PartnerScoringService::links()
            ->where('partner_id', $partner_id)
            ->filter(fn($row) => !$year || PartnerScoringService::linkDate($row)?->year === $year)
            ->sortByDesc(fn($row) => PartnerScoringService::linkDate($row))
            ->values();
    }

    /**
     * Итоги по платежам выборки
     *
     * @param Collection $payments
     * @return array
     */
    public static function paymentTotals(Collection $payments): array
    {
        $paid = $payments->where('state', 'paid');
        $overdue = $payments->where('state', 'overdue');

        return [
            'count' => $payments->count(),
            'paid' => $paid->count(),
            'overdue' => $overdue->count(),
            'paid_sum' => $paid->sum(fn($row) => (float) $row->amount_fact * PartnerScoringService::rate($row->currency_slug)),
            'overdue_sum' => $overdue->sum(fn($row) => (float) $row->amount_plan * PartnerScoringService::rate($row->currency_slug)),
        ];
    }
}
