<?php

namespace App\Modules\Pub\Analytics\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Реестр лицензий со сроками истечения.
 *
 * Лицензионные ключи лежат в карточках компаний, и увидеть их можно было только
 * зайдя в конкретную компанию — то есть узнать об истечении заранее было нельзя.
 * Здесь они собраны в один список, отсортированный по дате окончания: что
 * истекло, что истекает в ближайшие месяцы и на какие суммы.
 *
 * Суммы — из спецификации, к которой привязан ключ, приведены к рублям по
 * текущему курсу: это оценка продления, а не выставленный счёт.
 */
class LicenseRegistryService
{
    /** Горизонты, по которым раскладываются ключи */
    public const HORIZONS = [30, 60, 90];

    /**
     * Ключи с расчётом срока
     *
     * @param array $params [
     *     'horizon' => int|string|null — 30 / 60 / 90 / 'expired' / null (все),
     *     'partner' => int|null,
     *     'only_active' => bool — только активные ключи,
     *     'q' => string|null — ключ, компания, спецификация,
     * ]
     * @return Collection
     */
    public static function rows(array $params = []): Collection
    {
        $builder = DB::table('license_keys as k')
            ->leftJoin('companies as cm', 'cm.id', '=', 'k.company_id')
            ->leftJoin('contract_specifications as s', 's.id', '=', 'k.contract_specification_id')
            ->leftJoin('contracts as c', 'c.id', '=', 's.contract_id')
            ->leftJoin('partners as p', 'p.id', '=', 'c.partner_id')
            ->select([
                'k.id', 'k.key', 'k.active', 'k.active_from', 'k.active_to',
                'cm.id as company_id', 'cm.name as company_name',
                's.id as spec_id', 's.name as spec_name', 's.amount as spec_amount',
                's.status as spec_status', 's.currency_slug',
                'c.id as contract_id', 'c.number as contract_number', 'c.type as contract_type',
                'p.id as partner_id', 'p.name as partner_name', 'p.grade as partner_grade',
            ]);

        if (!empty($params['only_active'])) {
            $builder->where('k.active', 1);
        }

        if (!empty($params['partner'])) {
            $builder->where('c.partner_id', (int) $params['partner']);
        }

        if (!empty($params['q'])) {
            $like = '%' . trim($params['q']) . '%';
            $builder->where(function ($builder) use ($like) {
                $builder->where('k.key', 'like', $like)
                    ->orWhere('cm.name', 'like', $like)
                    ->orWhere('s.name', 'like', $like);
            });
        }

        $rows = collect($builder->get())->map(fn($row) => static::decorate($row));

        if (!empty($params['horizon'])) {
            $rows = static::horizon($rows, $params['horizon']);
        }

        return $rows
            ->sortBy(fn($row) => $row['active_to']?->timestamp ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * Дни до истечения, корзина и суммы
     *
     * @param mixed $row
     * @return array
     */
    public static function decorate($row): array
    {
        $to = $row->active_to ? Carbon::parse($row->active_to) : null;
        $from = $row->active_from ? Carbon::parse($row->active_from) : null;

        $days = $to ? (int) now()->startOfDay()->diffInDays($to->startOfDay(), false) : null;
        $bucket = static::bucket($days);

        return [
            'key' => $row,
            'id' => (int) $row->id,
            'code' => (string) $row->key,
            'active' => (bool) $row->active,
            'active_from' => $from,
            'active_to' => $to,
            'days' => $days,
            'bucket' => $bucket,
            'state' => static::state($bucket),
            'company' => ['id' => $row->company_id, 'name' => $row->company_name],
            'partner' => ['id' => $row->partner_id, 'name' => $row->partner_name, 'grade' => $row->partner_grade],
            'spec' => [
                'id' => $row->spec_id,
                'name' => $row->spec_name,
                'amount' => (float) $row->spec_amount,
                'currency' => $row->currency_slug,
                'canceled' => strtolower((string) $row->spec_status) === 'canceled',
            ],
            'contract' => ['id' => $row->contract_id, 'number' => $row->contract_number, 'type' => $row->contract_type],
            'amount_rub' => (float) $row->spec_amount * PartnerScoringService::rate($row->currency_slug),
        ];
    }

    /**
     * Корзина по остатку дней
     *
     * @param int|null $days
     * @return string
     */
    public static function bucket(?int $days): string
    {
        if ($days === null) return 'unknown';
        if ($days < 0) return 'expired';

        foreach (static::HORIZONS as $horizon) {
            if ($days <= $horizon) return 'soon' . $horizon;
        }

        return 'later';
    }

    /**
     * Оформление корзины
     *
     * @param string $bucket
     * @return array
     */
    public static function state(string $bucket): array
    {
        return match ($bucket) {
            'expired' => ['label' => 'Истекла', 'color' => 'danger'],
            'soon30' => ['label' => 'Меньше месяца', 'color' => 'danger'],
            'soon60' => ['label' => 'До двух месяцев', 'color' => 'warning'],
            'soon90' => ['label' => 'До трёх месяцев', 'color' => 'primary'],
            'later' => ['label' => 'Действует', 'color' => 'success'],
            default => ['label' => 'Срок не указан', 'color' => 'secondary'],
        };
    }

    /**
     * Отбор по горизонту. Горизонт включающий: 60 — это всё, что истекает
     * в течение 60 дней, вместе с уже истекшим.
     *
     * @param Collection $rows
     * @param int|string $horizon
     * @return Collection
     */
    public static function horizon(Collection $rows, int|string $horizon): Collection
    {
        if ($horizon === 'expired') {
            return $rows->filter(fn($row) => $row['bucket'] === 'expired')->values();
        }

        $limit = (int) $horizon;

        return $rows
            ->filter(fn($row) => $row['days'] !== null && $row['days'] <= $limit)
            ->values();
    }

    /**
     * Показатели сверху
     *
     * @param Collection $rows
     * @return array
     */
    public static function totals(Collection $rows): array
    {
        $ret = [
            'count' => $rows->count(),
            'expired' => 0,
            'expired_sum' => 0.0,
            'soon' => [],
            'later' => 0,
            'unknown' => 0,
            'amount' => (float) $rows->sum('amount_rub'),
            'companies' => $rows->pluck('company.id')->filter()->unique()->count(),
        ];

        foreach (static::HORIZONS as $horizon) {
            $ret['soon'][$horizon] = ['count' => 0, 'sum' => 0.0];
        }

        foreach ($rows as $row) {
            if ($row['bucket'] === 'expired') {
                $ret['expired']++;
                $ret['expired_sum'] += $row['amount_rub'];
                continue;
            }

            if ($row['bucket'] === 'unknown') {
                $ret['unknown']++;
                continue;
            }

            if ($row['bucket'] === 'later') {
                $ret['later']++;
                continue;
            }

            // корзины включающие: ключ на 45 дней попадает и в 60, и в 90
            foreach (static::HORIZONS as $horizon) {
                if ($row['days'] !== null && $row['days'] <= $horizon) {
                    $ret['soon'][$horizon]['count']++;
                    $ret['soon'][$horizon]['sum'] += $row['amount_rub'];
                }
            }
        }

        return $ret;
    }
}
