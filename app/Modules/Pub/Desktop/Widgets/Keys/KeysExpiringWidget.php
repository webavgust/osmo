<?php

namespace App\Modules\Pub\Desktop\Widgets\Keys;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\LicenseKey\Services\LicenseRenewalService;

/**
 * Истекающие ключи (patch v30): сколько активных ключей истекает в ближайшие N дней,
 * сколько уже истекло («хвост» license_expired_tail_days) и во сколько оценивается продление.
 *
 * Отбор — LicenseRenewalService::expiring() (тот же, что у реестра лицензий на горизонте:
 * активные ключи, 0 ≤ дней до конца ≤ N). Сумма продления — renewalAmount() в валюте
 * спецификации, переводится в валюту виджета по курсу на сегодня; ключи без курса
 * не суммируются (skipped). В сумму входят истёкшие ключи, если включено «Учитывать уже истёкшие».
 */
class KeysExpiringWidget extends Widget
{
    public static function id(): string
    {
        return 'keys_expiring';
    }

    public static function name(): string
    {
        return 'Истекающие ключи';
    }

    public static function category(): string
    {
        return 'keys';
    }

    public static function description(): string
    {
        return 'Сколько ключей истекает в ближайшие дни и во сколько обойдётся продление';
    }

    public static function icon(): string
    {
        return 'fa-key';
    }

    public static function sizes(): array
    {
        return ['4x2', '8x2', '4x4'];
    }

    public static function defaultSize(): string
    {
        return '4x2';
    }

    public static function order(): int
    {
        return 100;
    }

    public static function usesCurrency(): bool
    {
        return true;
    }

    public static function ttl(): int
    {
        return 600;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'days', 'type' => 'number', 'label' => 'Через сколько дней', 'default' => 30, 'min' => 1, 'max' => 365, 'hint' => 'Горизонты реестра — в константе license_horizons'],
            ['key' => 'show_amount', 'type' => 'bool', 'label' => 'Сумма продления', 'default' => true],
            ['key' => 'expired', 'type' => 'bool', 'label' => 'Учитывать уже истёкшие', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('analytics.licenses', array_filter([
            'horizon' => (int) $settings['days'],
            'only_active' => 1,
            'hide_expired' => empty($settings['expired']) ? 1 : null,
        ]));
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // 30 ключей в пределах горизонта: высокому блоку должно быть чем заполниться
        $names = ['ООО «Альфа»', 'АО «Вектор»', 'ООО «Гранит»', 'ЗАО «Дельта»', 'ООО «Енисей»', 'ООО «Жемчуг»',
            'АО «Зенит»', 'ООО «Ирбис»', 'ООО «Кедр»', 'АО «Лотос»', 'ООО «Магистраль»', 'ООО «Нева»', 'АО «Орион»',
            'ООО «Пульсар»', 'ООО «Радуга»'];
        $days = max(1, (int) $settings['days']);
        $soonest = [];
        for ($i = 0; $i < 30; $i++) {
            $soonest[] = [$names[$i % 15] . ($i >= 15 ? ' (филиал)' : ''), intdiv($i * $days, 30)];
        }

        return [
            'count' => 34, 'expired' => 2, 'amount' => 12600000.0, 'skipped' => 0,
            'symbol' => '₽', 'days' => (int) $settings['days'], 'tail' => LicenseRenewalService::EXPIRED_TAIL_DAYS,
            'soonest' => array_map(fn($row) => [
                'company' => $row[0], 'date' => now()->addDays($row[1])->format('d.m.Y'), 'days_left' => $row[1],
            ], $soonest),
        ];
    }

    /**
     * Счётчики, сумма продления и до 30 ближайших ключей
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['count', 'expired', 'amount', 'skipped', 'symbol', 'days', 'tail', 'soonest']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $days = (int) $settings['days'];
        $currency = $ctx->currencyFor($settings);

        // expiring() уже включает истёкшие за последние expiredTailDays() дней
        $rows = LicenseRenewalService::expiring($days);
        $soon = $rows->filter(fn($key) => $key->days_left >= 0)->values();
        $expired = $rows->filter(fn($key) => $key->days_left < 0);

        $amount = 0.0;
        $skipped = 0;
        foreach ($settings['expired'] ? $rows : $soon as $key) {
            $converted = CurrencyService::convertAmount((float) $key->renewal_amount, $key->specification?->currency_slug, $currency, now());

            if ($converted === null) {
                $skipped++;
                continue;
            }

            $amount += $converted;
        }

        return [
            'count' => $soon->count(),
            'expired' => $expired->count(),
            'amount' => round($amount, 2),
            'skipped' => $skipped,
            'symbol' => $ctx->symbol($currency),
            'days' => $days,
            'tail' => LicenseRenewalService::expiredTailDays(),
            // до 30 ближайших: высокий блок показывает их списком, лишние прячет .desk-fit
            'soonest' => $soon->take(30)->map(fn($key) => [
                'company' => (string) ($key->company?->name ?? '—'),
                'date' => $key->active_to->format('d.m.Y'),
                'days_left' => (int) $key->days_left,
            ])->all(),
        ];
    }
}
