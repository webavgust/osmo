<?php

namespace App\Modules\Bitrix\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Bitrix\Dashboard\Services\DashboardFilterService;
use App\Modules\Bitrix\Sync\Services\SyncService;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use Illuminate\Http\Request;
use App\Modules\Bitrix\Dashboard\Repositories\DashboardRepository;
use App\Modules\Bitrix\Dashboard\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;


class DashboardBoxController extends Controller
{
    public function currency()
    {
        $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
        $currencies = CurrencyRepository::getAll();
        $rates = CurrencyRepository::getRates(['date' => now(), 'returnFull' => true]);

        return View::make('bitrix.dashboard.box.currency', [
            'title' => 'Выбрать валюту',
            'currency_slug' => $currency_slug,
            'currencies' => $currencies,
            'rates' => $rates,
        ]);
    }

    public function filter()
    {
        return View::make('bitrix.dashboard.box.filter', array_merge([
            'title' => 'Фильтр',
            'filter' => DashboardFilterService::getFilter(),
        ], CrmDealRepository::getFilterOptions()));
    }

    public function table(string $mode)
    {
        $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
        $currencies = CurrencyRepository::getAll();
        $rates = CurrencyRepository::getRates(['date' => now(), 'returnFull' => true]);
        $service = new DashboardDataService();

        switch($mode) {
            case "sales":
                $data = $service->sales();
                $template = 'bitrix.dashboard.box.table_rub';
                $field = "Сумма сделки";
                $title = "Воронка по сделкам";
                break;
            case "licenses":
                $data = $service->licenses();
                $template = 'bitrix.dashboard.box.table';
                $field = "Сумма лицензий";
                $title = "Лицензии";
                break;
            case "services":
                $data = $service->services();
                $template = 'bitrix.dashboard.box.table';
                $field = "Сумма услуг";
                $title = "Услуги";
                break;
            case "devcost":
                $data = $service->devcost();
                $template = 'bitrix.dashboard.box.table';
                $field = "Стоимость";
                $title = "Стоимость разработки";
                break;
            case "platform":
                $data = $service->platform();
                $template = 'bitrix.dashboard.box.table';
                $field = "Стоимость";
                $title = "Стоимость платформенных доработок";
                break;
            case "services_raw":
                $data = $service->servicesRaw();
                $template = 'bitrix.dashboard.box.table_services';
                $field = "Стоимость";
                $title = "Услуги";
                break;


            default:
                abort(404);
        }


        return View::make($template, [
            'title' => $title,
            'currency_slug' => $currency_slug,
            'currencies' => $currencies,
            'field' => $field,
            'rates' => $rates,
            'data' => $data,
        ]);

    }

    public function industry_name(string $row, string $column)
    {
        $row = $row !== 'all' ? base64_decode(Str::replace("_", "/", $row)) : $row;
        $column = $column !== 'all' ? base64_decode(Str::replace("_", "/", $column)) : $column;

        $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
        $currencies = CurrencyRepository::getAll();
        $rates = CurrencyRepository::getRates(['date' => now(), 'returnFull' => true]);

        $service = new DashboardDataService();
        $data = $service->industry_name();

        if($row !== 'all') {
            if($column !== 'all') {
                $deals = $data['matrix'][$row][$column]['deals'];
            } else {
                $deals = $data['matrix'][$row]->flatMap->deals;
            }
        } else {
            if($column !== 'all') {
                $deals = collect();
                foreach($data['matrix'] as $rows) {
                    if(!empty($rows[$column]['deals']))
                        $deals = $deals->merge($rows[$column]['deals']);
                }
            } else {
                $deals = collect();
                foreach($data['matrix'] as $rows) {
                    foreach($rows as $column) {
                        if(!empty($column['deals']))
                        $deals = $deals->merge($column['deals']);
                    }
                }
            }
        }

        return View::make('bitrix.dashboard.box.industry_name', [
            'title' => 'Воронка в разрезе сферы деятельности и ответственных менеджеров',
            'currency_slug' => $currency_slug,
            'currencies' => $currencies,
            'rates' => $rates,
            'deals' => $deals->sortBy('title'),
        ]);
    }


    public function country_status_quarter(string $r1, string $r2, string $column)
    {
        $r1 = $r1 !== 'all' ? base64_decode(Str::replace("_", "/", $r1)) : $r1;
        $r2 = $r2 !== 'all' ? base64_decode(Str::replace("_", "/", $r2)) : $r2;
        $column = $column !== 'all' ? base64_decode(Str::replace("_", "/", $column)) : $column;

        $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
        $currencies = CurrencyRepository::getAll();
        $rates = CurrencyRepository::getRates(['date' => now(), 'returnFull' => true]);

        $service = new DashboardDataService();
        $data = $service->country_status_quarter();

        $data = collect($data['matrix']);

        $deals = collect();

        foreach($data as $country => $temp) {
            if($r1 !== 'all' && $r1 !== $country) continue;

            foreach($temp as $status => $temp2) {
                if($r2 !== 'all' && $r2 !== $status) continue;

                foreach($temp2 as $quarter => $ar) {
                    if($column !== 'all' && $column !== $quarter) continue;

                    $deals = $deals->merge($ar['deals']);
                }
            }
        }

        //$matrix[$country][$status][$quarter]['deals']++;



        return View::make('bitrix.dashboard.box.industry_name', [
            'title' => 'Вывод сделок',
            'currency_slug' => $currency_slug,
            'currencies' => $currencies,
            'rates' => $rates,
            'deals' => $deals->sortBy('title'),
        ]);
    }


    public function manager_status_quarter(string $r1, string $r2, string $column)
    {
        $r1 = $r1 !== 'all' ? base64_decode(Str::replace("_", "/", $r1)) : $r1;
        $r2 = $r2 !== 'all' ? base64_decode(Str::replace("_", "/", $r2)) : $r2;
        $column = $column !== 'all' ? base64_decode(Str::replace("_", "/", $column)) : $column;

        $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
        $currencies = CurrencyRepository::getAll();
        $rates = CurrencyRepository::getRates(['date' => now(), 'returnFull' => true]);

        $service = new DashboardDataService();
        $data = $service->manager_status_quarter();

        $data = collect($data['matrix']);

        $deals = collect();

        foreach($data as $country => $temp) {
            if($r1 !== 'all' && $r1 !== $country) continue;

            foreach($temp as $status => $temp2) {
                if($r2 !== 'all' && $r2 !== $status) continue;

                foreach($temp2 as $quarter => $ar) {
                    if($column !== 'all' && $column !== $quarter) continue;

                    $deals = $deals->merge($ar['deals']);
                }
            }
        }

        //$matrix[$country][$status][$quarter]['deals']++;



        return View::make('bitrix.dashboard.box.industry_name', [
            'title' => 'Вывод сделок',
            'currency_slug' => $currency_slug,
            'currencies' => $currencies,
            'rates' => $rates,
            'deals' => $deals->sortBy('title'),
        ]);
    }


    public function country_status_month(string $r1, string $r2, string $column)
    {
        $r1 = $r1 !== 'all' ? base64_decode(Str::replace("_", "/", $r1)) : $r1;
        $r2 = $r2 !== 'all' ? base64_decode(Str::replace("_", "/", $r2)) : $r2;
        $column = $column !== 'all' ? base64_decode(Str::replace("_", "/", $column)) : $column;

        $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
        $currencies = CurrencyRepository::getAll();
        $rates = CurrencyRepository::getRates(['date' => now(), 'returnFull' => true]);

        $service = new DashboardDataService();
        $data = $service->country_status_month();

        $data = collect($data['matrix']);

        $deals = collect();

        foreach($data as $country => $temp) {
            if($r1 !== 'all' && $r1 !== $country) continue;

            foreach($temp as $status => $temp2) {
                if($r2 !== 'all' && $r2 !== $status) continue;

                foreach($temp2 as $quarter => $ar) {
                    if($column !== 'all' && $column !== $quarter) continue;

                    $deals = $deals->merge($ar['deals']);
                }
            }
        }

        //$matrix[$country][$status][$quarter]['deals']++;



        return View::make('bitrix.dashboard.box.industry_name', [
            'title' => 'Вывод сделок',
            'currency_slug' => $currency_slug,
            'currencies' => $currencies,
            'rates' => $rates,
            'deals' => $deals->sortBy('title'),
        ]);
    }

    public function status_country_month(string $r1, string $r2, string $column)
    {
        $r1 = $r1 !== 'all' ? base64_decode(Str::replace("_", "/", $r1)) : $r1;
        $r2 = $r2 !== 'all' ? base64_decode(Str::replace("_", "/", $r2)) : $r2;
        $column = $column !== 'all' ? base64_decode(Str::replace("_", "/", $column)) : $column;

        $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
        $currencies = CurrencyRepository::getAll();
        $rates = CurrencyRepository::getRates(['date' => now(), 'returnFull' => true]);

        $service = new DashboardDataService();
        $data = $service->status_country_month();

        $data = collect($data['matrix']);

        $deals = collect();

        foreach($data as $status => $temp) {
            if($r1 !== 'all' && $r1 !== $status) continue;

            foreach($temp as $country => $temp2) {
                if($r2 !== 'all' && $r2 !== $country) continue;

                foreach($temp2 as $quarter => $ar) {
                    if($column !== 'all' && $column !== $quarter) continue;

                    $deals = $deals->merge($ar['deals']);
                }
            }
        }


        return View::make('bitrix.dashboard.box.industry_name', [
            'title' => 'Вывод сделок',
            'currency_slug' => $currency_slug,
            'currencies' => $currencies,
            'rates' => $rates,
            'deals' => $deals->sortBy('title'),
        ]);
    }

}
