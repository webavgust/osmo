<?php

namespace App\Modules\Pub\Currency\Services;

use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    /**
     * Курсы по датам в пределах запроса: ['2025-03-01' => ['RUB' => 1, 'USD' => 92.5, ...]]
     * Платёжный календарь спрашивает курс для каждой строки — без этого
     * получилось бы по запросу в базу на платёж.
     *
     * @var array
     */
    protected static array $rates_by_date = [];

    /** RUR из старых записей — это те же рубли */
    public const ALIASES = ['RUR' => 'RUB'];

    public static function updateRates() {

        $http = Http::get('https://v6.exchangerate-api.com/v6/a26ac05327899c0494755112/latest/RUB');

        $data = $http->json();
        if($data['result'] !== 'success')
            return;

        $rates = CurrencyRepository::getForeign();
        foreach($rates as $rate) {
            $currency = 1 / ($data['conversion_rates'][$rate->slug] ?? 1);

            DB::table('currency_rates')->where('date', now()->format("Y-m-d"))->where('slug', $rate->slug)->delete();

            // Записи нет, вставляем ее
            DB::table('currency_rates')->insert([
                'date' => now()->format("Y-m-d"),
                'slug' => $rate->slug,
                'amount' => $currency
            ]);

            dump([
                'date' => now()->format("Y-m-d"),
                'slug' => $rate->slug,
                'amount' => $currency
            ]);
        }

    }

    public static function getConvertRates(): array
    {
        $rates = CurrencyRepository::getRates();

        if (empty($rates)) {
            return [];
        }

        $conversionRates = [];

        foreach ($rates as $fromCurrency => $fromRate) {
            $conversionRates[$fromCurrency] = [];

            foreach ($rates as $toCurrency => $toRate) {
                if ($toRate == 0) {
                    // Обработка случая с нулевым курсом
                    $conversionRates[$fromCurrency][$toCurrency] = 0;
                    continue;
                }

                $conversionRates[$fromCurrency][$toCurrency] = $fromRate / $toRate;
            }
        }

        return $conversionRates;
    }

    /**
     * Курс перевода одной валюты в другую на дату
     *
     * В currency_rates хранится, сколько рублей стоит единица валюты
     * (`amount`), поэтому перевод из A в B — это отношение их курсов.
     * Берётся последний курс НЕ ПОЗЖЕ указанной даты: так факт пересчитывается
     * по курсу на день поступления, даже если запись за этот день не заводили.
     *
     * @param Carbon $date_fact Дата, на которую нужен курс
     * @param mixed $slug Исходная валюта (RUB, USD, CNY…)
     * @param mixed $currency_target Валюта результата
     * @return float|null Множитель: сумма в целевой валюте = сумма × курс.
     *                    null — курса на эту дату нет, пересчитать нельзя.
     */
    public static function getConvertRateForDate(Carbon $date_fact, mixed $slug, mixed $currency_target)
    {
        $slug = static::slug($slug);
        $currency_target = static::slug($currency_target);

        if ($slug === $currency_target) return 1.0;

        $rates = static::ratesForDate($date_fact);

        $from = (float) ($rates[$slug] ?? 0);
        $to = (float) ($rates[$currency_target] ?? 0);

        // курса нет — молча считать по единице нельзя, это исказит суммы
        if ($from <= 0 || $to <= 0) return null;

        return $from / $to;
    }

    /**
     * Перевести сумму в другую валюту на дату
     *
     * @param float $amount
     * @param mixed $slug
     * @param mixed $currency_target
     * @param Carbon|null $date Дата курса; по умолчанию — сегодня
     * @return float|null null — курс неизвестен
     */
    public static function convertAmount(float $amount, mixed $slug, mixed $currency_target = null, Carbon $date = null)
    {
        $rate = static::getConvertRateForDate(
            $date ?: now(),
            $slug,
            $currency_target ?: Currency::CURRENCY_DEFAULT
        );

        return $rate === null ? null : $amount * $rate;
    }

    /**
     * Курсы на дату (с запоминанием в пределах запроса)
     *
     * @param Carbon $date
     * @return array
     */
    public static function ratesForDate(Carbon $date): array
    {
        $key = $date->format('Y-m-d');

        if (!isset(static::$rates_by_date[$key])) {
            $rates = CurrencyRepository::getRates(['date' => $key]);

            // алиасы, чтобы RUR не терялся
            foreach (static::ALIASES as $alias => $slug) {
                if (!isset($rates[$alias]) && isset($rates[$slug])) $rates[$alias] = $rates[$slug];
            }

            static::$rates_by_date[$key] = $rates;
        }

        return static::$rates_by_date[$key];
    }

    /**
     * Нормализованный код валюты
     *
     * @param mixed $slug
     * @return string
     */
    public static function slug(mixed $slug): string
    {
        $slug = strtoupper(trim((string) $slug));
        if ($slug === '') $slug = Currency::CURRENCY_DEFAULT;

        return static::ALIASES[$slug] ?? $slug;
    }
}
