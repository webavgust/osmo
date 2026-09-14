<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Курсы валют (patch v30): курс ЦБ на последнюю дату, изменение ко вчера и график
 * за N дней.
 *
 * Курсы лежат в currency_rates (их обновляет команда обновления курсов): одна строка —
 * дата, код валюты, рублей за единицу. Курса может не быть за сегодня (выходные,
 * не отработала команда), поэтому берётся последняя дата, где курс есть, а если она
 * старше двух дней — виджет это показывает.
 */
class RatesWidget extends Widget
{
    /** За сколько дней тянуть историю под график */
    public const HISTORY_DAYS = 90;

    public static function id(): string
    {
        return 'rates';
    }

    public static function name(): string
    {
        return 'Курсы валют';
    }

    public static function category(): string
    {
        return 'common';
    }

    public static function description(): string
    {
        return 'Курс ЦБ по выбранным валютам, изменение ко вчера и график за период';
    }

    public static function icon(): string
    {
        return 'fa-money-bill-transfer';
    }

    public static function sizes(): array
    {
        return ['8x4', '4x2', '8x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 60;
    }

    public static function ttl(): int
    {
        return 1800;
    }

    public static function available(User $user): bool
    {
        return true;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'only', 'type' => 'list', 'label' => 'Валюты', 'default' => ['USD', 'EUR', 'CNY'],
                'hint' => 'Коды через запятую; пусто — все валюты портала'],
            ['key' => 'chart', 'type' => 'bool', 'label' => 'График за период', 'default' => true],
            ['key' => 'days', 'type' => 'number', 'label' => 'Дней на графике', 'default' => 30, 'min' => 7, 'max' => 90],
        ];
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // десяток валют: высоким блокам есть чем заполнить список
        $sample = [
            ['USD', 'Доллар США', '$', 91.42, 0.3],
            ['EUR', 'Евро', '€', 99.10, -0.2],
            ['CNY', 'Юань', '¥', 12.55, 0.1],
            ['GBP', 'Фунт стерлингов', '£', 116.37, 0.4],
            ['CHF', 'Швейцарский франк', '₣', 103.84, -0.1],
            ['JPY', 'Японская иена', '¥', 0.62, 0.0],
            ['KZT', 'Казахстанский тенге', '₸', 0.19, -0.3],
            ['BYN', 'Белорусский рубль', 'Br', 27.95, 0.2],
            ['TRY', 'Турецкая лира', '₺', 2.71, -0.6],
            ['AED', 'Дирхам ОАЭ', 'د.إ', 24.89, 0.3],
        ];

        $rows = [];
        foreach ($sample as $index => [$slug, $name, $symbol, $rate, $delta]) {
            // история за 30 дней: плавная волна вокруг курса, у каждой валюты своя фаза
            $history = [];
            for ($day = 29; $day >= 0; $day--) {
                $history[] = round($rate * (1 + 0.012 * sin(($day + $index * 3) / 4) - 0.0004 * $day), 4);
            }
            $history[29] = $rate;

            $rows[] = ['slug' => $slug, 'name' => $name, 'symbol' => $symbol, 'rate' => $rate, 'delta' => $delta, 'history' => $history];
        }

        return [
            'date' => now()->format('d.m.Y'),
            'stale' => false,
            'days' => 30,
            'rows' => $rows,
        ];
    }

    /**
     * Курсы выбранных валют: последний курс, изменение к предыдущей дате и история
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['date', 'stale', 'days', 'rows' => [['slug', 'name', 'symbol', 'rate', 'delta', 'history']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $days = max(7, min(static::HISTORY_DAYS, (int) $settings['days']));
        $wanted = collect((array) $settings['only'])
            ->filter(fn($slug) => is_scalar($slug) && trim((string) $slug) !== '')
            ->map(fn($slug) => strtoupper(trim((string) $slug)))
            ->all();

        $currencies = Currency::query()->get()->keyBy(fn($currency) => strtoupper((string) $currency->slug));
        $last_date = DB::table('currency_rates')->max('date');

        if (!$last_date) {
            return ['date' => null, 'stale' => true, 'days' => $days, 'rows' => []];
        }

        $from = Carbon::parse($last_date)->subDays($days);
        $rates = DB::table('currency_rates')
            ->where('date', '>=', $from->format('Y-m-d'))
            ->orderBy('date')
            ->get(['date', 'slug', 'amount']);

        // код валюты => дата => курс
        $by_slug = [];
        foreach ($rates as $rate) {
            $slug = strtoupper((string) $rate->slug);
            if ($slug === Currency::CURRENCY_DEFAULT) continue;
            if (!empty($wanted) && !in_array($slug, $wanted, true)) continue;

            $by_slug[$slug][(string) $rate->date] = (float) $rate->amount;
        }

        $rows = [];
        foreach ($by_slug as $slug => $series) {
            $values = array_values($series);
            $last = end($values);
            $previous = count($values) > 1 ? $values[count($values) - 2] : null;
            $currency = $currencies->get($slug);

            $rows[] = [
                'slug' => $slug,
                'name' => $currency ? (string) $currency->name : $slug,
                'symbol' => $currency && $currency->symbol ? (string) $currency->symbol : $slug,
                'rate' => $last,
                'delta' => $previous ? round(($last - $previous) / $previous * 100, 2) : null,
                'history' => $values,
            ];
        }

        // порядок как в настройке, остальные по алфавиту
        usort($rows, function ($a, $b) use ($wanted) {
            $ia = array_search($a['slug'], $wanted, true);
            $ib = array_search($b['slug'], $wanted, true);
            if ($ia === false && $ib === false) return strcmp($a['slug'], $b['slug']);
            if ($ia === false) return 1;
            if ($ib === false) return -1;

            return $ia <=> $ib;
        });

        return [
            'date' => Carbon::parse($last_date)->format('d.m.Y'),
            // курс старше двух дней — команда обновления курсов, похоже, не отработала
            'stale' => Carbon::parse($last_date)->diffInDays(now()) > 2,
            'days' => $days,
            'rows' => $rows,
        ];
    }
}
