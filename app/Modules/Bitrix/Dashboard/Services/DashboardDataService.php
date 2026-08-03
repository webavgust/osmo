<?php

namespace App\Modules\Bitrix\Dashboard\Services;

use App\Modules\Bitrix\CrmCompany\Models\CrmCompany;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Models\CrmDealUf;
use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Payment\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardDataService
{
    private $deals;

    public function __construct()
    {
        // получим срез конвертированных данных
        $deals = CrmDealRepository::getFiltered();

        $this->deals = $this->deals_convert_currency($deals);
    }

    public function filter($deals)
    {
        return $deals->filter(function($deal) {
            return $this->deals->contains($deal);
        });
    }

    public function deals_convert_currency($deals)
    {
        $currency_target = Cache::get('dashboard_currency') ?? "RUB";
        $convert_rates = CurrencyService::getConvertRates();

        $deals->map(function ($item) use ($currency_target, $convert_rates) {
            $item->opportunity_RUB = $item->opportunity * $convert_rates[$item->currency_id][$currency_target];

            // конвертируем доп.поля
            if($currency_target !== 'RUB') {
                $item->uf_crm_1718977752420_RUB = $item->uf_crm_1718977752420 * $convert_rates['RUB'][$currency_target];
                $item->uf_crm_1718977763677_RUB = $item->uf_crm_1718977763677 * $convert_rates['RUB'][$currency_target];
                $item->uf_crm_1723814702122_RUB = $item->uf_crm_1723814702122 * $convert_rates['RUB'][$currency_target];
                $item->uf_crm_1725019324602_RUB = $item->uf_crm_1725019324602 * $convert_rates['RUB'][$currency_target];
            } else {
                $item->uf_crm_1718977752420_RUB = $item->uf_crm_1718977752420;
                $item->uf_crm_1718977763677_RUB = $item->uf_crm_1718977763677;
                $item->uf_crm_1723814702122_RUB = $item->uf_crm_1723814702122;
                $item->uf_crm_1725019324602_RUB = $item->uf_crm_1725019324602;
            }
        });

        return $deals;
    }

    public static function scopeStatuses(Collection $collection)
    {
        return $collection->whereIn('stage_name', [
            'Acceptance tests',
//            'Closing documents',
            'Competition/tender',
            'Execution (POST-PAYMENT)',
            'Invoice + Specification',
            'Lead',
            'Pilot project',
            'Presentation',
            'Research',
            'TCP',
            'Contracting',
        ])
        ->whereNotIn('id', [3])
        ;
    }
    public function sales()
    {
        $deals = $this->scopeStatuses($this->deals);
        return [
            'deals' => $deals,
            'field' => 'opportunity',
            'amount' => $deals->sum('opportunity_RUB')
        ];
    }
    public function licenses()
    {
        $deals = $this->scopeStatuses($this->deals);
        return [
            'deals' => $deals,
            'field' => 'uf_crm_1718977752420',
            'amount' => $deals
                ->sum('uf_crm_1718977752420_RUB')
        ];
    }
    public function services()
    {
        $deals = $this->scopeStatuses($this->deals);
        return [
            'deals' => $deals,
            'field' => 'uf_crm_1718977763677',
            'amount' => $deals
                ->sum('uf_crm_1718977763677_RUB')
        ];
    }
    public function devcost()
    {
        $deals = $this->scopeStatuses($this->deals);
        return [
            'deals' => $deals,
            'field' => 'uf_crm_1723814702122',
            'amount' => $deals
                ->sum('uf_crm_1723814702122_RUB')
        ];
    }
    public function platform()
    {
        $deals = $this->scopeStatuses($this->deals);
        return [
            'deals' => $deals,
            'field' => 'uf_crm_1725019324602',
            'amount' => $deals
                ->sum('uf_crm_1725019324602_RUB')
        ];
    }
    public function servicesRaw()
    {
        $deals = $this->scopeStatuses($this->deals);
        $deals->map(function($item) {
            $item->service_raw = $item->uf_crm_1718977763677_RUB - $item->uf_crm_1723814702122_RUB - $item->uf_crm_1725019324602_RUB;
        });

        return [
            'deals' => $deals,
            'field' => 'service_raw',
            'amount' =>$deals->sum('service_raw')
        ];
    }

    public function country_status_quarter()
    {
        // получим все deals
        $deals = CrmDeal::all();
        $deals = $this->scopeStatuses($deals);
        $deals = $this->deals_convert_currency($deals);

        $matrix = [];
        $columns =  ['2026q1', '2026q2', '2026q3', '2026q4'];
        foreach ($deals as $deal) {
            if(!$deal->opportunity_RUB) continue;

            $country = $deal->crm_company?->companyUf?->uf_crm_1719404976291 ?? "Неизвестно";
            $quarter = $deal->dealUf?->uf_crm_1722255711522 ?? null;
            $status = $deal->stage_name;

            if(!in_array($quarter, $columns)) continue;

            if(empty($matrix[$country]))
                $matrix[$country] = [];

            if(empty($matrix[$country][$status]))
                $matrix[$country][$status] = [];


            if(empty($matrix[$country][$status][$quarter]))
                $matrix[$country][$status][$quarter] = [
                    'deals' => [],
                    'amount' => 0
                ];
            $matrix[$country][$status][$quarter]['deals'][] = $deal;
            $matrix[$country][$status][$quarter]['amount'] += $deal->opportunity_RUB;
        }

        $matrix = collect($matrix)->sortKeys();
        return ['matrix' => $matrix, 'columns' => $columns];
    }

    public function industry_name()
    {
        $arIndustries = CrmCompany::pluck('industry_name')->unique()->sort();

        $arData = collect();
        foreach($arIndustries as $industry) {
            $deals = CrmDeal::whereHas('customer', function($builder) use ($industry) {
                $builder->where('industry_name', $industry);
            })
            ->where('opportunity', '>', 0)
            ->get();

            $deals = $this->filter($deals);
            $deals = $this->scopeStatuses($deals);
            $deals = $this->deals_convert_currency($deals);

            $arData[$industry] =  $deals->groupBy('assigned_by_name');
        }

        // Без сферы
        $deals = CrmDeal::whereHas('customer', function($builder) {
            $builder->whereNull('industry_name');
        })
        ->where('opportunity', '>', 0)
        ->get();

        $deals = $this->filter($deals);
        $deals = $this->scopeStatuses($deals);
        $deals = $this->deals_convert_currency($deals);

        $arData["Неизвестно"] =  $deals->groupBy('assigned_by_name');



        // сформируем матрицу
        $columns = $arData->flatMap->keys()->unique()->sort();
        $rows = $arData->keys()->unique()->sort();

        $matrix = collect();
        foreach($rows as $row) {
            $matrix[$row] = collect();

            foreach($columns as $column) {
                if(!empty($arData[$row][$column])) {
                    $matrix[$row][$column] = [
                        'deals' => $arData[$row][$column],
                        'amount' => $arData[$row][$column]->sum('opportunity_RUB')
                    ];
                }
            }
        }


        return ['matrix' => $matrix, 'rows' => $rows, 'columns' => $columns->values()];
    }

    public function manager_status_quarter()
    {
        // получим все deals
        $deals = CrmDeal::all();
        $deals = $this->scopeStatuses($deals);
        $deals = $this->deals_convert_currency($deals);

        $matrix = [];
        $columns =  ['2026q1', '2026q2', '2026q3', '2026q4'];
        foreach ($deals as $deal) {
            if(!$deal->opportunity_RUB) continue;

            $manager = $deal->manager ?? "Неизвестно";
            $quarter = $deal->dealUf?->uf_crm_1722255711522 ?? null;
            $status = $deal->stage_name;

            if(!in_array($quarter, $columns)) continue;

            if(empty($matrix[$manager]))
                $matrix[$manager] = [];

            if(empty($matrix[$manager][$status]))
                $matrix[$manager][$status] = [];


            if(empty($matrix[$manager][$status][$quarter]))
                $matrix[$manager][$status][$quarter] = [
                    'deals' => [],
                    'amount' => 0
                ];
            $matrix[$manager][$status][$quarter]['deals'][] = $deal;
            $matrix[$manager][$status][$quarter]['amount'] += $deal->opportunity_RUB;
        }

        $matrix = collect($matrix);
        $matrix = collect($matrix)->sortKeys();


        return ['matrix' => $matrix, 'columns' => $columns];
    }


    public function country_status_month()
    {
        // получим все deals
        $deals = CrmDeal::all();
        $deals = $this->scopeStatuses($deals);
        $deals = $this->deals_convert_currency($deals);

        $matrix = [];


        $start = Carbon::now()->startOfMonth();
        $monthsCount = 5;

        $columns = [];
        $quarter = [];

        for ($i = 0; $i <= $monthsCount; $i++) {
            $date = $start->copy()->addMonths($i);
            $columns[] = $date->format('m');
            $quarter[] = $date->format('Y') . 'q' . $date->quarter;
        }


        $quarter = collect($quarter)->unique()->toArray();

        foreach ($deals as $deal) {
            if(!$deal->opportunity_RUB) continue;

            $country = $deal->crm_company?->companyUf?->uf_crm_1719404976291 ?? "Неизвестно";
            $month = $deal->dealUf?->uf_crm_1736778153503 ?? null;
            $status = $deal->stage_name;

            if(!in_array($month, $columns)) continue;
            if(!in_array($deal->dealUf?->uf_crm_1722255711522, $quarter)) continue;


            if(empty($matrix[$country]))
                $matrix[$country] = [];

            if(empty($matrix[$country][$status]))
                $matrix[$country][$status] = [];


            if(empty($matrix[$country][$status][$month]))
                $matrix[$country][$status][$month] = [
                    'deals' => [],
                    'amount' => 0
                ];
            $matrix[$country][$status][$month]['deals'][] = $deal;
            $matrix[$country][$status][$month]['amount'] += $deal->opportunity_RUB;
        }

        $matrix = collect($matrix)->sortKeys();

        return ['matrix' => $matrix, 'columns' => $columns];
    }
    public function status_country_month()
    {
        // получим все deals
        $deals = CrmDeal::all();
        $deals = $this->scopeStatuses($deals);
        $deals = $this->deals_convert_currency($deals);

        $matrix = [];


        $start = Carbon::now()->startOfMonth();
        $monthsCount = 5;

        $columns = [];
        $quarter = [];

        for ($i = 0; $i <= $monthsCount; $i++) {
            $date = $start->copy()->addMonths($i);
            $columns[] = $date->format('m');
            $quarter[] = $date->format('Y') . 'q' . $date->quarter;
        }

        $quarter = collect($quarter)->unique()->toArray();

        foreach ($deals as $deal) {
            if(!$deal->opportunity_RUB) continue;

            $country = $deal->crm_company?->companyUf?->uf_crm_1719404976291 ?? "Неизвестно";
            $status = $deal->stage_name;

            $month = $deal->dealUf?->uf_crm_1736778153503 ?? null;

            if(!in_array($month, $columns)) continue;
            if(!in_array($deal->dealUf?->uf_crm_1722255711522, $quarter)) continue;


            if(empty($matrix[$status]))
                $matrix[$status] = [];

            if(empty($matrix[$status][$country]))
                $matrix[$status][$country] = [];


            if(empty($matrix[$status][$country][$month]))
                $matrix[$status][$country][$month] = [
                    'deals' => [],
                    'amount' => 0
                ];
            $matrix[$status][$country][$month]['deals'][] = $deal;
            $matrix[$status][$country][$month]['amount'] += $deal->opportunity_RUB;
        }

        $matrix = collect($matrix)->sortKeys();

        return ['matrix' => $matrix, 'columns' => $columns];
    }

    public static function temp__table()
    {
        $builder = CrmCompany::where('id', '>', 0);
        $companies = $builder->get();


        $i = 0;
        foreach($companies as $company) {
            $grid[$i] = []; // строка


            $country = $company->companyUf?->uf_crm_1719404976291 ?? "Неизвестно";
            $company_title = $company->title ?? '?';
//            $reponsibles = $company->deals->pluck('assigned_by')->unique()->join('<br/>');

            $uf = CrmDealUf::where('uf_crm_1717755645', $company->title)->get();
            $responsibles = [];
            foreach($uf as $once) {
                if(!empty($once->deal->assigned_by))
                    $responsibles[] = $once->deal->assigned_by;
            }

            $responsibles = implode("<br/>", array_unique($responsibles));


            $grid[$i][0] = ['rowspan' => 1, 'cell' => $country, 'system' => $country];
            $grid[$i][1] = ['rowspan' => 1, 'cell' => $company_title, 'system' => $company_title];
            $grid[$i][2] = ['rowspan' => 1, 'cell' => $company->created_by_name, 'system' => $company->created_by_name];
            $grid[$i][3] = ['rowspan' => 1, 'cell' => $responsibles, 'system' => $responsibles];
//

            $i++;
        }

        // Sort the grid by 'cell' values of the first 9 columns
        usort($grid, function($a, $b) {
            for ($i = 0; $i <= 1; $i++) {
                $cellA = $a[$i]['cell'] ?? '';
                $cellB = $b[$i]['cell'] ?? '';
                if ($cellA < $cellB) return -1;
                if ($cellA > $cellB) return 1;
            }
            return 0; // If all first 9 cells are equal
        });


        // склеим колонки
        // Merge rows based on consecutive identical 'system' values
        for ($col = 0; $col <= 1; $col++) {
            $count = 1; // Count of consecutive rows
            $countRows = count($grid);
            for ($row = 1; $row < $countRows; $row++) {
                if (!empty($grid[$row - 1][$col]['system']) && $grid[$row][$col]['system'] === $grid[$row - 1][$col]['system']) {
                    $count++;
                } else {
                    if ($count > 1) {
                        // Set rowspan for the first occurrence
                        $grid[$row - $count][$col]['rowspan'] = $count;
                        // Set other occurrences to null
                        for ($j = 1; $j < $count; $j++) {
                            $grid[$row - $j][$col] = null;
                        }
                    }
                    $count = 1; // Reset count for new value
                }
            }
            // Check for the last group
            if ($count > 1) {
                $grid[$row - $count][$col]['rowspan'] = $count;
                for ($j = 1; $j < $count; $j++) {
                    $grid[$row - $j][$col] = null;
                }
            }
        }

        return $grid;
    }

    public function anna_text()
    {
        // получим все deals
        $deals = CrmDeal::all();
        $deals = $this->scopeStatuses($deals);
        $deals = $this->deals_convert_currency($deals);

        $arStatus = [];
        foreach($deals as $deal) {
            if(empty($arStatus[$deal->stage_name]))
                $arStatus[$deal->stage_name] = 0;
            $arStatus[$deal->stage_name] += $deal->opportunity_RUB;
        }

        ksort($arStatus);



        dd($arStatus, array_sum($arStatus));
    }
}
