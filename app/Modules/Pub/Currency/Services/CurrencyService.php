<?php

namespace App\Modules\Pub\Currency\Services;

use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
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

    public static function getConvertRateForDate(Carbon $date_fact, mixed $slug, mixed $currency_target)
    {

    }
}
