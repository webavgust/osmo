<?php

namespace App\Modules\Pub\Currency\Repository;

use App\Modules\Pub\Currency\Models\Currency;
use Illuminate\Support\Facades\DB;

class CurrencyRepository
{

    public static function get(string $slug)
    {
        return Currency::where('slug', $slug)->first();
    }

    public static function getAll()
    {
        return Currency::all()->keyBy('slug');
    }

    public static function getForeign()
    {
        return Currency::whereNot('slug', 'RUB')->get()->keyBy('slug');
    }

    public static function getRates($arParams = [])
    {
        $builder = DB::table('currency_rates')
            ->select('slug', 'amount', 'date', 'id')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->distinct('slug');

        if(!empty($arParams['date'])) {
            $builder->where('date', '<=', $arParams['date']);
        }
        $rates = $builder->get();
        // строки идут от новых к старым: берём первую по каждой валюте.
        // keyBy() оставлял последнюю — то есть самый старый курс в базе
        if(!empty($arParams['returnFull']))
            return $rates->unique('slug')->keyBy('slug');

        $rates = $rates
            ->groupBy('slug')
            ->map(function ($item) {
                return $item->first();
            });

        if(empty($arParams['ignore_rub']))
            $ratesArray = ['RUB' => 1];


        foreach ($rates as $rate) {
            $ratesArray[$rate->slug] = $rate->amount;
        }

        return $ratesArray;
    }

}
