<?php

namespace App\Modules\Pub\LicenseKey\Services;

use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use Illuminate\Support\Carbon;

/**
 * Продление лицензий.
 *
 * Считает, что истекает в ближайшее время, и во сколько это оценивается
 * по сумме спецификации, которой лицензия принадлежит.
 */
class LicenseRenewalService
{
    /**
     * Горизонты в днях, по которым группируем.
     * По умолчанию; рабочее значение — consts.license_horizons (читать через horizons())
     */
    public const HORIZONS = [30, 60, 90];

    /**
     * Сколько дней «просрочки» ещё показываем (истекло, но можно вернуть).
     * По умолчанию; рабочее значение — consts.license_expired_tail_days (читать через expiredTailDays())
     */
    public const EXPIRED_TAIL_DAYS = 30;

    /**
     * Горизонты в днях по возрастанию (consts.license_horizons, по умолчанию HORIZONS).
     * Та же константа задаёт горизонты реестра лицензий (LicenseRegistryService).
     *
     * @return int[]
     */
    public static function horizons(): array
    {
        $horizons = collect(Constant::json('license_horizons', static::HORIZONS))
            ->filter(fn($days) => is_numeric($days) && (int) $days > 0)
            ->map(fn($days) => (int) $days)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $horizons ?: static::HORIZONS;
    }

    /**
     * Сколько дней после истечения лицензия ещё показывается
     * (consts.license_expired_tail_days, по умолчанию EXPIRED_TAIL_DAYS)
     *
     * @return int
     */
    public static function expiredTailDays(): int
    {
        return max(0, Constant::int('license_expired_tail_days', static::EXPIRED_TAIL_DAYS));
    }

    /**
     * Лицензии, требующие внимания
     *
     * @param int $days Горизонт в днях
     * @return \Illuminate\Support\Collection
     */
    public static function expiring(int $days = 90)
    {
        $from = now()->subDays(static::expiredTailDays())->startOfDay();
        $to = now()->addDays($days)->endOfDay();

        return LicenseKey::query()
            ->with(['company', 'specification.contract'])
            ->where('active', true)
            ->whereNotNull('active_to')
            ->whereBetween('active_to', [$from, $to])
            ->orderBy('active_to')
            ->get()
            ->map(function (LicenseKey $key) {
                $days_left = (int) now()->startOfDay()->diffInDays($key->active_to, false);

                $key->setAttribute('days_left', $days_left);
                $key->setAttribute('is_expired', $days_left < 0);
                $key->setAttribute('renewal_amount', static::renewalAmount($key));
                $key->setAttribute('urgency', static::urgency($days_left));

                return $key;
            });
    }

    /**
     * Сводка для виджета дашборда
     *
     * @return array
     */
    public static function summary(): array
    {
        $horizons = static::horizons();
        $rows = static::expiring(max($horizons));

        $ret = [
            'total' => $rows->count(),
            'amount' => $rows->sum('renewal_amount'),
            'expired' => $rows->where('is_expired', true)->count(),
            'horizons' => [],
        ];

        foreach ($horizons as $days) {
            $slice = $rows->filter(
                fn($key) => $key->days_left >= 0 && $key->days_left <= $days
            );

            $ret['horizons'][$days] = [
                'count' => $slice->count(),
                'amount' => $slice->sum('renewal_amount'),
            ];
        }

        // ближайшая дата истечения
        $ret['nearest'] = $rows->where('is_expired', false)->first()?->active_to;

        return $ret;
    }

    /**
     * Во сколько оценивается продление.
     *
     * Точной цены продления в базе нет, поэтому берём сумму спецификации,
     * по которой лицензия была выдана — это лучшая доступная оценка.
     *
     * @param LicenseKey $key
     * @return float
     */
    public static function renewalAmount(LicenseKey $key): float
    {
        $spec = $key->specification;
        if (empty($spec)) return 0;

        // если по спецификации выдано несколько ключей — делим сумму между ними
        $keys_count = max(1, $spec->license_keys()->count());

        return round((float) ($spec->amount ?? 0) / $keys_count, 2);
    }

    /**
     * Насколько срочно: expired / urgent / soon / planned
     *
     * @param int $days_left
     * @return array
     */
    public static function urgency(int $days_left): array
    {
        return match (true) {
            $days_left < 0 => ['code' => 'expired', 'label' => 'Истекла', 'color' => 'dark'],
            $days_left <= 30 => ['code' => 'urgent', 'label' => 'Срочно', 'color' => 'danger'],
            $days_left <= 60 => ['code' => 'soon', 'label' => 'Скоро', 'color' => 'warning'],
            default => ['code' => 'planned', 'label' => 'В плане', 'color' => 'info'],
        };
    }
}
