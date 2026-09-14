<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;

/**
 * Выбор валюты стола (patch v30).
 *
 * Меняет контекст стола: денежные виджеты с настройкой «Со стола» пересчитываются
 * в выбранную валюту. Рядом (на ширине 8) — курс выбранной валюты к рублю на сегодня.
 */
class CurrencyWidget extends Widget
{
    public static function id(): string
    {
        return 'currency';
    }

    public static function name(): string
    {
        return 'Выбор валюты';
    }

    public static function category(): string
    {
        return 'common';
    }

    public static function description(): string
    {
        return 'Валюта стола: денежные виджеты «со стола» считаются в ней';
    }

    public static function icon(): string
    {
        return 'fa-coins';
    }

    public static function sizes(): array
    {
        return ['4x2', '8x2'];
    }

    public static function defaultSize(): string
    {
        return '4x2';
    }

    public static function order(): int
    {
        return 100;
    }

    public static function ttl(): int
    {
        return 3600;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'only', 'type' => 'list', 'label' => 'Валюты в списке', 'default' => [], 'hint' => 'Пусто — все валюты портала'],
        ];
    }

    /**
     * Образец для превью: валюта стола не рубль, чтобы в карточке было видно курс
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        $data = $this->data($settings, $ctx);

        if ($data['current'] === Currency::CURRENCY_DEFAULT) {
            $others = array_keys(array_diff_key($data['currencies'], [Currency::CURRENCY_DEFAULT => true]));
            $slug = in_array('USD', $others, true) ? 'USD' : ($others[0] ?? null);

            if ($slug !== null) {
                $rate = CurrencyService::getConvertRateForDate(now(), $slug, Currency::CURRENCY_DEFAULT);
                $data['current'] = $slug;
                $data['rate'] = $rate === null ? 90.0 : (float) $rate;
            }
        }

        return $data;
    }

    /**
     * Валюты для списка, текущая валюта стола и её курс к рублю
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['currencies' => [slug => ['slug', 'name', 'symbol']], 'current', 'rate', 'rate_date']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $only = collect((array) $settings['only'])
            ->filter(fn($slug) => is_scalar($slug) && trim((string) $slug) !== '')
            ->map(fn($slug) => CurrencyService::slug($slug))
            ->all();

        $currencies = [];
        foreach (DesktopContext::currencies() as $slug => $currency) {
            // текущая валюта стола остаётся в списке, даже если её нет в «only»:
            // иначе select покажет выбранной чужую валюту
            if (!empty($only) && !in_array($slug, $only, true) && $slug !== $ctx->currency) {
                continue;
            }

            $currencies[$slug] = [
                'slug' => $slug,
                'name' => (string) $currency->name,
                'symbol' => (string) ($currency->symbol ?: $slug),
            ];
        }

        $rate = $ctx->currency === Currency::CURRENCY_DEFAULT
            ? 1.0
            : CurrencyService::getConvertRateForDate(now(), $ctx->currency, Currency::CURRENCY_DEFAULT);

        return [
            'currencies' => $currencies,
            'current' => $ctx->currency,
            'rate' => $rate === null ? null : (float) $rate,
            'rate_date' => now()->format('d.m.Y'),
        ];
    }
}
