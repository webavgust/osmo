<?php

namespace App\Modules\Pub\Desktop\Services;

use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;

/**
 * Контекст рабочего стола (patch v30): валюта и период, общие для виджетов.
 *
 * Виджет «Выбор валюты» / «Период» меняет контекст, остальные виджеты читают
 * его через currencyFor() / periodFor(): настройка виджета 'desk' — взять со
 * стола, иначе — своё значение (конкретная валюта принудительно).
 */
class DesktopContext
{
    /** Значение настройки «взять со стола» */
    public const FROM_DESK = 'desk';

    /** Периоды: код => подпись */
    public const PERIODS = [
        'month' => 'Текущий месяц',
        'quarter' => 'Текущий квартал',
        'year' => 'Текущий год',
        'last30' => 'Последние 30 дней',
        'last90' => 'Последние 90 дней',
        'last365' => 'Последние 12 месяцев',
        'prev_month' => 'Прошлый месяц',
        'prev_quarter' => 'Прошлый квартал',
        'prev_year' => 'Прошлый год',
    ];

    public const DEFAULTS = ['currency' => Currency::CURRENCY_DEFAULT, 'period' => 'quarter'];

    protected static ?array $currencies = null;

    public string $currency;
    public string $period;
    public ?User $user;

    /**
     * @param string|null $currency код валюты; неизвестная — рубли
     * @param string|null $period код периода из PERIODS; неизвестный — квартал
     * @param User|null $user
     */
    public function __construct(?string $currency = null, ?string $period = null, ?User $user = null)
    {
        $this->currency = static::validCurrency($currency) ?? static::DEFAULTS['currency'];
        $this->period = array_key_exists((string) $period, static::PERIODS) ? (string) $period : static::DEFAULTS['period'];
        $this->user = $user;
    }

    /**
     * Контекст из массива ['currency' => ..., 'period' => ...]
     *
     * @param array $context
     * @param User|null $user
     * @return static
     */
    public static function make(array $context = [], ?User $user = null): static
    {
        return new static($context['currency'] ?? null, $context['period'] ?? null, $user ?? auth()->user());
    }

    /**
     * @return array ['currency' => 'RUB', 'period' => 'quarter']
     */
    public function toArray(): array
    {
        return ['currency' => $this->currency, 'period' => $this->period];
    }

    /**
     * Валюта виджета: со стола или своя
     *
     * @param array $settings нормализованные настройки виджета
     * @return string код валюты
     */
    public function currencyFor(array $settings): string
    {
        $value = (string) ($settings['currency'] ?? static::FROM_DESK);

        return $value === static::FROM_DESK ? $this->currency : (static::validCurrency($value) ?? $this->currency);
    }

    /**
     * Символ валюты: ₽, $, ¥
     *
     * @param string|null $slug null — валюта стола
     * @return string
     */
    public function symbol(?string $slug = null): string
    {
        $slug = CurrencyService::slug($slug ?? $this->currency);

        return (string) (static::currencies()[$slug]->symbol ?? $slug);
    }

    /**
     * Код периода виджета: со стола или свой
     *
     * @param array $settings
     * @return string
     */
    public function periodKeyFor(array $settings): string
    {
        $value = (string) ($settings['period'] ?? static::FROM_DESK);

        return array_key_exists($value, static::PERIODS) ? $value : $this->period;
    }

    /**
     * Период виджета с границами
     *
     * @param array $settings
     * @return array ['key', 'label', 'from' => Carbon, 'to' => Carbon, 'dates' => 'd.m.Y – d.m.Y']
     */
    public function periodFor(array $settings): array
    {
        $key = $this->periodKeyFor($settings);
        [$from, $to] = static::range($key);

        return [
            'key' => $key,
            'label' => static::PERIODS[$key],
            'from' => $from,
            'to' => $to,
            'dates' => $from->format('d.m.Y') . ' – ' . $to->format('d.m.Y'),
        ];
    }

    /**
     * Границы периода
     *
     * @param string $key код из PERIODS
     * @param Carbon|null $now
     * @return Carbon[] [from, to]
     */
    public static function range(string $key, ?Carbon $now = null): array
    {
        $now = ($now ?? now())->copy();

        return match ($key) {
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last30' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'last90' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            'last365' => [$now->copy()->subDays(364)->startOfDay(), $now->copy()->endOfDay()],
            'prev_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'prev_quarter' => [$now->copy()->subQuarterNoOverflow()->startOfQuarter(), $now->copy()->subQuarterNoOverflow()->endOfQuarter()],
            'prev_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            default => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
        };
    }

    /**
     * Предыдущий такой же период — для сравнения «к прошлому»:
     * месяц/квартал/год сдвигаются на свою единицу, «последние N дней» — на N дней
     *
     * @param string $key
     * @param Carbon|null $now
     * @return Carbon[] [from, to]
     */
    public static function previousRange(string $key, ?Carbon $now = null): array
    {
        [$from, $to] = static::range($key, $now);

        return match ($key) {
            'month', 'prev_month' => [$from->copy()->subMonthNoOverflow()->startOfMonth(), $from->copy()->subMonthNoOverflow()->endOfMonth()],
            'quarter', 'prev_quarter' => [$from->copy()->subQuarterNoOverflow()->startOfQuarter(), $from->copy()->subQuarterNoOverflow()->endOfQuarter()],
            'year', 'prev_year' => [$from->copy()->subYear()->startOfYear(), $from->copy()->subYear()->endOfYear()],
            default => (function () use ($from, $to) {
                $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

                return [$from->copy()->subDays($days), $to->copy()->subDays($days)];
            })(),
        };
    }

    /**
     * Часть ключа кэша данных виджета
     *
     * @return array
     */
    public function cacheKey(): array
    {
        return [$this->user?->id, $this->currency, $this->period, now()->format('Y-m-d')];
    }

    /**
     * Валюты портала: код => модель
     *
     * @return array
     */
    public static function currencies(): array
    {
        if (static::$currencies === null) {
            static::$currencies = CurrencyRepository::getAll()->all();
        }

        return static::$currencies;
    }

    /**
     * Проверенный код валюты или null
     *
     * @param string|null $slug
     * @return string|null
     */
    public static function validCurrency(?string $slug): ?string
    {
        if ($slug === null || trim($slug) === '' || $slug === static::FROM_DESK) {
            return null;
        }

        $slug = CurrencyService::slug($slug);

        return array_key_exists($slug, static::currencies()) ? $slug : null;
    }
}
