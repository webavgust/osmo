<?php

namespace App\Modules\Pub\Report\Services;


use App\Modules\Pub\CalculationLesson\Models\CalculationLesson;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\LicenseKey\Repository\LicenseKeyRepository;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Organization\Model\Organization;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Payment\Models\Payment;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Scenario\Models\Scenario;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\ScenarioGroup\Models\ScenarioGroup;
use Illuminate\Support\Carbon;

class ReportService
{
    public static function getPaymentSummary(Currency $currency, array $filter = null)
    {


        $convert_rates = CurrencyService::getConvertRates();
        $currency_target = $currency->slug;

        $builder = Payment::where('id', '>', 0)->whereDoesntHave('contract_specification', function($builder) {
            $builder->whereIn('status', [ContractSpecificationStatus::CANCELED]);
        });


        if(empty($filter['cb_include_finished'])) {
            $builder->whereDoesntHave('contract_specification', function($builder) {
                $builder->whereNotIn('status', [ContractSpecificationStatus::PROCESSING]);
            });
        }


        // получим срез данных по фильтру
        if(!empty($filter)) {
            foreach($filter as $key => $value) {
                switch($key) {
                    case "pay_mode":
                        switch($value) {
                            case "payed":
                                $builder->whereNotNull('date_fact');
                                break;
                            case "unpayed":
                                $builder->whereNull('date_fact');
                                break;
                        }
                        break;
                    case "date_both":
                    case "date_plan":
                    case "date_fact":
                    case "date_realization":
                        $dates = explode(' - ', $value);
                        $date_from = Carbon::createFromFormat('d.m.Y', trim($dates[0]))->startOfDay();
                        $date_to = Carbon::createFromFormat('d.m.Y', trim($dates[1]))->endOfDay();

                        switch($key) {
                            case "date_plan":
                            case "date_fact":
                                $builder->whereBetween($key, [$date_from, $date_to]);
                                break;
                            case "date_both":
                                $builder->where(function($builder) use ($date_from, $date_to) {
                                    $builder->whereBetween('date_plan', [$date_from, $date_to])
                                        ->orWhereBetween('date_fact', [$date_from, $date_to]);
                                });
                                break;
                        }
                        break;
                    case "amount_fact":
                        if(!empty($value['from']))
                            $builder->where('amount_fact', '>=', $value['from']);
                        if(!empty($value['to']))
                            $builder->where('amount_fact', '<=', $value['to']);
                        break;
                    case "contract_number":
                        $builder->whereHas('contract_specification.contract', function($builder) use ($value) {
                            $builder->where('number', 'like', "%{$value}%");
                        });
                        break;
                    case "company":
                        $builder->whereHas('contract_specification.company', function($builder) use ($value) {
                            $builder->where('id', $value);
                        });

                        break;
                    case "user_id":
                        $builder->whereHas('user', function($builder) use ($value) {
                            $builder->whereIn('id', $value);
                        });
                        break;
                }

            }
        }

        $payments = $builder->get();


        // пост обработка фильтра
        $payments = $payments->filter(function($item) use ($filter) {
            // фактическая оплата отличается от плановой
            if(!empty($filter['cb_payment_diff']) && $filter['cb_payment_diff'])
                return !empty($item->amount_fact) && $item->amount_fact !== $item->amount_plan ? $item : null;

            // просрочка оплаты
            if(!empty($filter['cb_payment_late']) && $filter['cb_payment_late'])
                return empty($item->amount_fact) && ($item->date_plan?->isPast() ?? false) ? $item : null;

            return $item;
        });

        $grid = [];

        $i = 0;
        foreach($payments as $payment) {
            $grid[$i] = []; // строка

            $spec = $payment->contract_specification; if(empty($spec->id)) continue;
            $contract = $spec->contract; if(empty($contract)) continue;
            $company = $spec->company;
            if(empty($company)) {
                dd($spec);
            }
            $partner = $company->partner;

            $grid[$i][0] = ['rowspan' => 1, 'cell' => $partner->name, 'system' => $partner->id];
//            $grid[$i][2] = ['rowspan' => 1, 'cell' => $proposal->name ?? $contract->proposal_name ?? 'Неизвестно', 'link' => !empty($proposal) ? route('proposal.detail', [$proposal, $proposal->iteration]) : null, 'system' => $proposal->id ?? '0-' . $company->id ];
//            $grid[$i][3] = ['rowspan' => 1, 'cell' => $proposal->cost_total ?? '', 'system' => $proposal->id ?? '0-' . $company->id ];


            $grid[$i][1] = ['rowspan' => 1, 'cell' => $contract->type ?? '', 'system' => $contract->id ];
            $grid[$i][2] = ['rowspan' => 1, 'cell' => $contract->number ?? '', 'system' => $contract->id, 'org' => $contract->organization];

            $grid[$i][3] = ['rowspan' => 1, 'cell' => $company->name, 'system' => $company->id];

            $grid[$i][4] = ['rowspan' => 1, 'cell' => $spec->name ?? '', 'system' => $spec->id ];
            $grid[$i][5] = ['rowspan' => 1, 'cell' => '', 'system' => $spec->id ];

            $grid[$i][6] = [
                'rowspan' => 1,
                'cell' => $spec->amount_past * $convert_rates[$spec->currency->slug][$currency_target],
                'system' => $spec->id,
                'org' => $contract->organization,
                'alt' => $spec->currency->slug === $currency->slug || !$spec->amount_past ? null : (tools()->cost_normalize($spec->amount_past))  . ' ' . $spec->currency->symbol
            ];
            $grid[$i][7] = [
                'rowspan' => 1,
                'cell' => ($spec->amount_all - $spec->amount_past) * $convert_rates[$spec->currency->slug][$currency_target],
                'system' => $spec->id,
                'org' => $contract->organization,
                'alt' => $spec->currency->slug === $currency->slug || !($spec->amount_all - $spec->amount_past) ? null : (tools()->cost_normalize($spec->amount_all - $spec->amount_past))  . ' ' . $spec->currency->symbol
            ];
            $grid[$i][8] = ['rowspan' => 1, 'cell' => $payment->date_plan, 'system' => $payment->id, 'is_unknown' => $payment->is_unknown ];
            $grid[$i][9] = [
                'rowspan' => 1,
                'cell' => $payment->amount_plan * $convert_rates[$spec->currency->slug][$currency_target],
                'system' => $payment->id,
                'org' => $contract->organization,
                'alt' => $spec->currency->slug === $currency->slug || !$payment->amount_plan ? null : (tools()->cost_normalize($payment->amount_plan))  . ' ' . $spec->currency->symbol
            ];
            $grid[$i][10] = ['rowspan' => 1, 'cell' => $payment->date_fact, 'system' => $payment->id, 'pay' => $spec->amount_all - $spec->amount_past];

            $convert_rate = CurrencyRepository::getRates(['date' => $payment->date_fact]);
            $rate = $convert_rate[$spec->currency->slug] / $convert_rate[$currency_target];


            $grid[$i][11] = [
                'rowspan' => 1,
                'cell' => $payment->amount_fact * $rate,
                'system' => $payment->id ,
                'alt' => $spec->currency->slug === $currency->slug || !$payment->amount_fact ? null : (tools()->cost_normalize($payment->amount_fact))  . ' ' . $spec->currency->symbol . ' * ' . round($rate, $rate < 1 ? 4 : 2)
            ];
            $grid[$i][12] = ['rowspan' => 1, 'cell' => $spec->is_signed, 'system' => $payment->id ];
            $grid[$i][13] = ['rowspan' => 1, 'cell' => $payment->user?->initials ?? '......', 'system' => $payment->id ];

            $i++;
        }

        // Sort the grid by 'cell' values of the first 9 columns
        usort($grid, function($a, $b) {
            for ($i = 0; $i < 9; $i++) {
                $cellA = $a[$i]['cell'] ?? '';
                $cellB = $b[$i]['cell'] ?? '';
                if ($cellA < $cellB) return -1;
                if ($cellA > $cellB) return 1;
            }
            return 0; // If all first 9 cells are equal
        });


        // склеим колонки
        // Merge rows based on consecutive identical 'system' values
        for ($col = 0; $col <= 13; $col++) {
            $count = 1; // Count of consecutive rows
            for ($row = 1; $row < count($grid); $row++) {
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






    public static function scenario_popular()
    {
        $arScenarios = [];
        $arServices = [];
        $services_count = $scenarios_count = $services_sold = $scenarios_sold = 0;

        $proposals = Proposal::whereIn('id', function ($query) {
            $query->from('proposals')->groupBy('group')->selectRaw('MAX(id)');
        })
        ->whereHas('variants')
        ->get();
        foreach($proposals as $proposal) {
            $variant = $proposal->variants()->orderBy('cost_total', 'asc')->first();
            $scenarios_count += $variant->proposal_scenarios->count();

            foreach($variant->proposal_scenarios as $scenario) {
                if(empty($scenario->scenario)) {
                    dd($scenario);
                }
                if(empty($arScenarios[$scenario->scenario->id])) $arScenarios[$scenario->scenario->id] = ['sold' => 0, 'count' => 0, 'place' => 0, 'instance' => Scenario::find($scenario->scenario->id)];

                $arScenarios[$scenario->scenario->id]['count'] = $arScenarios[$scenario->scenario->id]['count'] + 1;
                $arScenarios[$scenario->scenario->id]['sold'] = $arScenarios[$scenario->scenario->id]['sold'] + $scenario->count;


                $scenarios_sold += $scenario->count;

                $services_count += $scenario->scenario->neuroservices->count();

                foreach($scenario->scenario->neuroservices as $service) {
                    if(empty($arServices[$service->id])) $arServices[$service->id] = ['sold' => 0, 'count' => 0, 'place' => 0, 'instance' => Neuroservice::find($service->id)];
                    $arServices[$service->id]['count'] = $arServices[$service->id]['count'] + 1;
                    $arServices[$service->id]['sold'] = $arServices[$service->id]['sold'] + $scenario->count;


                    $services_sold += $scenario->count;
                }
            }
        }

        // отсортируем и проставим места SERVICES
        uasort($arServices, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        // Проставляем место (place) и конечное место (end_place)
        $place = 1;
        $lastCount = null;
        $countAtPlace = 0; // Счетчик для одинаковых значений count
        $startPlace = 1; // Начальное место для группы

        foreach ($arServices as $id => &$service) {
            if ($lastCount === null || $service['count'] !== $lastCount) {
                // Если значение count изменилось, обновляем место
                if ($countAtPlace > 0) {
                    // Если есть записи с одинаковым count, обновляем end_place для них
                    for ($i = 0; $i < $countAtPlace; $i++) {
                        $arServices[$lastIds[$i]]['end_place'] = $place - 1; // Устанавливаем end_place для предыдущих
                    }
                }

                $service['place'] = $place; // Устанавливаем текущее место
                $lastCount = $service['count'];
                $countAtPlace = 1; // Сбрасываем счетчик для нового значения
                $lastIds = [$id]; // Сохраняем ID для обновления end_place
                $startPlace = $place; // Запоминаем стартовое место
            } else {
                // Если значение count такое же, просто увеличиваем счетчик
                $service['place'] = $startPlace; // Устанавливаем то же место
                $lastIds[] = $id; // Добавляем ID в массив для обновления end_place
                $countAtPlace++;
            }

            $place++; // Увеличиваем общее место
        }

// Обновляем end_place для последних записей
        if ($countAtPlace > 0) {
            for ($i = 0; $i < $countAtPlace; $i++) {
                $arServices[$lastIds[$i]]['end_place'] = $place - 1; // Устанавливаем end_place для последних
            }
        }



        // отсортируем и проставим места SERVICES
        uasort($arScenarios, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        $place = 1;
        $lastCount = null;
        $countAtPlace = 0; // Счетчик для одинаковых значений count
        $startPlace = 1; // Начальное место для группы

        foreach ($arScenarios as $id => &$scenario) {
            if ($lastCount === null || $scenario['count'] !== $lastCount) {
                // Если значение count изменилось, обновляем место
                if ($countAtPlace > 0) {
                    // Если есть записи с одинаковым count, обновляем end_place для них
                    for ($i = 0; $i < $countAtPlace; $i++) {
                        $arScenarios[$lastIds[$i]]['end_place'] = $place - 1; // Устанавливаем end_place для предыдущих
                    }
                }

                $scenario['place'] = $place; // Устанавливаем текущее место
                $lastCount = $scenario['count'];
                $countAtPlace = 1; // Сбрасываем счетчик для нового значения
                $lastIds = [$id]; // Сохраняем ID для обновления end_place
                $startPlace = $place; // Запоминаем стартовое место
            } else {
                // Если значение count такое же, просто увеличиваем счетчик
                $scenario['place'] = $startPlace; // Устанавливаем то же место
                $lastIds[] = $id; // Добавляем ID в массив для обновления end_place
                $countAtPlace++;
            }

            $place++; // Увеличиваем общее место
        }

// Обновляем end_place для последних записей
        if ($countAtPlace > 0) {
            for ($i = 0; $i < $countAtPlace; $i++) {
                $arScenarios[$lastIds[$i]]['end_place'] = $place - 1; // Устанавливаем end_place для последних
            }
        }

        return [
            'services' => ['rows' => $arServices, 'count' => $services_count, 'sold' => $services_sold],
            'scenarios' => ['rows' => $arScenarios, 'count' => $scenarios_count, 'sold' => $scenarios_sold],
        ];
    }



    public static function getScenarios()
    {
        // отберём нужные договоры

        $groups = ScenarioGroup::orderBy('sort')->get();


        $grid = [];

        $i = 0;
        foreach($groups as $group) {
            foreach($group->scenarios()->where('active', 1)->get() as $scenario) {
                foreach($scenario->neuroservices()->orderBy('name')->get() as $neuro) {
                    $grid[$i] = []; // строка

                    $grid[$i][0] = ['rowspan' => 1, 'cell' => $group->name, 'system' => $group->id];
                    $grid[$i][1] = ['rowspan' => 1, 'cell' => $scenario->number, 'system' => $scenario->id];
                    $grid[$i][2] = ['rowspan' => 1, 'cell' => $scenario->name, 'system' => $scenario->id];
                    $grid[$i][3] = ['rowspan' => 1, 'cell' => $neuro->name, 'system' => $i];

                    $i++;
                }
            }
        }

        // Sort the grid by 'cell' values of the first 9 columns
        usort($grid, function($a, $b) {
            for ($i = 0; $i <= 2; $i++) {
                $cellA = $a[$i]['cell'] ?? '';
                $cellB = $b[$i]['cell'] ?? '';
                if ($cellA < $cellB) return -1;
                if ($cellA > $cellB) return 1;
            }
            return 0; // If all first 9 cells are equal
        });

        // склеим колонки
        // Merge rows based on consecutive identical 'system' values
        for ($col = 0; $col <= 3; $col++) {
            $count = 1; // Count of consecutive rows
            for ($row = 1; $row < count($grid); $row++) {
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

    public static function getScenariosSpecsSummary(array $filter = null)
    {
        // отберём нужные договоры
        $arContracts = Contract::where('type', ContractType::LICENSE)
            ->whereHas('organization', function($builder) {
                $builder->where('id', '!=', Organization::NEUROLIS);
            })
            ->get();


        $arPartners = $arContracts->pluck('partner')->unique();
        $arCompanies = $arContracts->pluck('company')->unique();


        $grid = [];

        $i = 0;
        foreach($arPartners as $partner) {
            foreach($partner->companies->whereIn('id', $arCompanies->pluck('id')) as $company) {
                foreach($company->contracts->whereIn('id', $arContracts->pluck('id')) as $contract) {
                    if(!empty($contract->proposal)) {
                        foreach ($contract->proposal->variants->where('is_main')->first()->proposal_scenarios->sortBy('name') as $scenario) {
                            foreach ($scenario->neuroservices->sortBy('name') as $neuro) {
                                $grid[$i] = []; // строка

                                $grid[$i][0] = ['rowspan' => 1, 'cell' => $partner->name, 'system' => $partner->id];
                                $grid[$i][1] = ['rowspan' => 1, 'cell' => $company->name, 'system' => $company->id];
                                $grid[$i][2] = ['rowspan' => 1, 'cell' => $contract->proposal->name, 'link' => route('proposal.detail', [$contract->proposal, $contract->proposal->iteration]), 'system' => $contract->proposal->id];
                                $grid[$i][3] = ['rowspan' => 1, 'cell' => $contract->number, 'system' => $contract->id, 'org' => $contract->organization];
                                $grid[$i][4] = ['rowspan' => 1, 'cell' => $contract->contract_specifications->pluck('name')->join('<br/>'), 'system' => 'specs_' . $contract->id];
                                $grid[$i][5] = ['rowspan' => 1, 'cell' => $scenario->scenario->name, 'system' => $scenario->id];
                                $grid[$i][6] = ['rowspan' => 1, 'cell' => $neuro->name, 'system' => $neuro->id];

                                $i++;
                            }
                        }
                    }
                    foreach($contract->contract_specifications as $spec) {
//                        if($contract->id == 42) dd($spec->contract_specification_scenarios);
                        foreach($spec->contract_specification_scenarios as $scenario) {
                            if(!empty($scenario->scenario)) {
                                foreach ($scenario->scenario->neuroservices->sortBy('name') as $neuro) {
                                    $grid[$i] = []; // строка

                                    $grid[$i][0] = ['rowspan' => 1, 'cell' => $partner->name, 'system' => $partner->id];
                                    $grid[$i][1] = ['rowspan' => 1, 'cell' => $company->name, 'system' => $company->id];

                                    if(!empty($spec->contract->proposal)) {
                                        $grid[$i][2] = ['rowspan' => 1, 'cell' => $spec->contract->proposal->name, 'link' => route('proposal.detail', [$spec->contract->proposal, $spec->contract->proposal->iteration]), 'system' => $spec->contract->proposal->name];
                                    } else {
                                        $grid[$i][2] = ['rowspan' => 1, 'cell' => '<i class="fa-solid fa-ellipsis"></i>', 'class' => ['bold_red'], 'system' => 'proposal_' . $contract->id];
                                    }

                                    $grid[$i][3] = ['rowspan' => 1, 'cell' => $contract->number, 'system' => $contract->id, 'org' => $contract->organization];
                                    $grid[$i][4] = ['rowspan' => 1, 'cell' => $spec->name, 'system' => 'specs_' . $contract->id];
                                    $grid[$i][5] = ['rowspan' => 1, 'cell' => $scenario->scenario->name, 'system' => $scenario->id];
                                    $grid[$i][6] = ['rowspan' => 1, 'cell' => $neuro->name, 'system' => $spec->id . '_' . $neuro->id];

                                    $i++;
                                }
                            } else {
                                $grid[$i] = []; // строка

                                $grid[$i][0] = ['rowspan' => 1, 'cell' => $partner->name, 'system' => $partner->id];
                                $grid[$i][1] = ['rowspan' => 1, 'cell' => $company->name, 'system' => $company->id];
                                if(!empty($spec->contract->proposal)) {
                                    $grid[$i][2] = ['rowspan' => 1, 'cell' => $spec->contract->proposal->name, 'link' => route('proposal.detail', [$spec->contract->proposal, $spec->contract->proposal->iteration]), 'system' => $spec->contract->proposal->name];
                                } else {
                                    $grid[$i][2] = ['rowspan' => 1, 'cell' => '<i class="fa-solid fa-ellipsis"></i>', 'class' => ['bold_red'], 'system' => 'proposal_' . $contract->id];
                                }

                                $grid[$i][3] = ['rowspan' => 1, 'cell' => $contract->number, 'system' => $contract->id, 'org' => $contract->organization];
                                $grid[$i][4] = ['rowspan' => 1, 'cell' => $spec->name, 'system' => 'specs_' . $contract->id];
                                $grid[$i][5] = ['rowspan' => 1, 'cell' => $scenario->name, 'class' => ['manual'], 'system' => $scenario->id];
                                $grid[$i][6] = ['rowspan' => 1, 'cell' => '', 'class' => ['manual'], 'system' => $spec->id . '_0'];

                                $i++;
                            }
                        }
                    }
                }
            }
        }

        // Sort the grid by 'cell' values of the first 9 columns
        usort($grid, function($a, $b) {
            for ($i = 0; $i <= 2; $i++) {
                $cellA = $a[$i]['cell'] ?? '';
                $cellB = $b[$i]['cell'] ?? '';
                if ($cellA < $cellB) return -1;
                if ($cellA > $cellB) return 1;
            }
            return 0; // If all first 9 cells are equal
        });

        // склеим колонки
        // Merge rows based on consecutive identical 'system' values
        for ($col = 0; $col <= 6; $col++) {
            $count = 1; // Count of consecutive rows
            for ($row = 1; $row < count($grid); $row++) {
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

    public static function getLicenseKeysSummary(array $filter = null)
    {

        // отберём нужные договоры

        $builder = LicenseKey::where('id', '>', 0);

        // получим срез данных по фильтру
        if(!empty($filter)) {
            foreach($filter as $key => $value) {
                switch($key) {
                    case "active_from":
                    case "active_to":
                        $dates = explode(' - ', $value);
                        $date_from = Carbon::createFromFormat('d.m.Y', trim($dates[0]))->startOfDay();
                        $date_to = Carbon::createFromFormat('d.m.Y', trim($dates[1]))->endOfDay();

                        switch($key) {
                            case "active_from":
                            case "active_to":
                                $builder->whereBetween($key, [$date_from, $date_to]);
                                break;
                        }
                        break;
                    case "contract_number":
                        $builder->whereHas('specification.contract', function($builder) use ($value) {
                            $builder->where('number', 'like', "%{$value}%");
                        });
                        break;
                    case "company":
                        $builder->whereHas('company', function($builder) use ($value) {
                            $builder->where('id', $value);
                        });
                        break;
                    case "cb_expired_3":
                        $builder->where('active_to', '>', now());
                        $builder->where('active_to', '<', now()->endOfDay()->addMonths(3));
                        break;
                }
            }
        }


        if(empty($filter['cb_show_unactive']))
            $builder->where('active', true);


        $keys = $builder->get();




        $i = 0;
        foreach ($keys->sortBy('name') as $key) {
            $grid[$i] = []; // строка

            $grid[$i][0] = ['rowspan' => 1, 'cell' => $key->company?->partner?->name ?? '?', 'system' => $key->company?->partner?->id ?? 0];
            $grid[$i][1] = ['rowspan' => 1, 'cell' => $key->company?->name ?? '?', 'system' => $key->company->id ?? 0];

            $spec = $key->specification;
            $contract = $spec?->contract ?? null;

            if(!empty($contract)) {
//                if(!empty($spec->contract?->proposal)) {
//                    $grid[$i][2] = ['rowspan' => 1, 'cell' => $spec->contract->proposal->name, 'link' => route('proposal.detail', [$spec->contract->proposal, $spec->contract->proposal->iteration]), 'system' => $spec->contract->proposal->id];
//                } else {
//                    $grid[$i][2] = ['rowspan' => 1, 'cell' => '<i class="fa-solid fa-ellipsis"></i>', 'class' => ['text-muted'], 'system' => 'proposal_' . $contract->id];
//                }
                $grid[$i][2] = ['rowspan' => 1, 'cell' => $contract->number, 'system' => $contract->id, 'org' => $contract->organization];
                $grid[$i][3] = ['rowspan' => 1, 'cell' => $spec->name, 'system' => $spec->id ?? 0 ];

            } else {
//                $grid[$i][2] = ['rowspan' => 1, 'cell' => '<i class="fa-solid fa-ellipsis"></i>', 'class' => ['text-muted'], 'system' => $key->company];
                $grid[$i][2] = ['rowspan' => 1, 'cell' => '<i class="fa-solid fa-ellipsis"></i>', 'class' => ['text-muted'], 'system' => $key->company];
                $grid[$i][3] = ['rowspan' => 1, 'cell' => '<i class="fa-solid fa-ellipsis"></i>', 'class' => ['text-muted'], 'system' => $key->company];
            }



            $grid[$i][4] = ['rowspan' => 1, 'cell' => $key->key . (!$key->active ? ' (не активен)' : ''),
                'class' => [
                    'unactive' => !$key->active,
                    'expired' => $key->active && $key->active_to->lessThan(now()),
                    'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                ], 'system' => 'key_' . $key->id];
            $grid[$i][5] = ['rowspan' => 1, 'cell' => $key->active_from->format("d.m.Y"),
                'class' => [
                    'unactive' => !$key->active,
                    'expired' => $key->active && $key->active_to->lessThan(now()),
                    'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                ], 'system' => 'active_from_' . $key->id];
            $grid[$i][6] = ['rowspan' => 1, 'cell' => $key->active_to->format("d.m.Y"),
                'class' => [
                    'unactive' => !$key->active,
                    'expired' => $key->active && $key->active_to->lessThan(now()),
                    'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                ], 'system' => 'active_to' . $key->id];
            $grid[$i][7] = ['rowspan' => 1, 'cell' => $key->count,
                'class' => [
                    'unactive' => !$key->active,
                    'expired' => $key->active && $key->active_to->lessThan(now()),
                    'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                ], 'system' => 'active_to' . $key->id];

            //
            if(!empty($key->comment)) {
                $json = json_decode($key->comment, 1);
                $ret = '<table class="m-0 border-0 w-100">';
                foreach($json as $str => $count) {
                    $ret .= "<tr><th class='text-primary p-1'>{$str}</th><td class='p-1 text-center border-0'>{$count}</td></tr>";
                }
                $ret .= '</table>';
            } else {
                $ret = '';
            }


            $grid[$i][8] = ['rowspan' => 1, 'cell' => $ret,
                'class' => [
                    'unactive' => !$key->active,
                    'expired' => $key->active && $key->active_to->lessThan(now()),
                    'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                    'text-wrap',
                    'fs-1',
                ], 'system' => 'active_to' . $key->id];

            $i++;
        }

        // Sort the grid by 'cell' values of the first 9 columns
        usort($grid, function($a, $b) {
            for ($i = 0; $i < 4; $i++) {
                $cellA = $a[$i]['cell'] ?? '';
                $cellB = $b[$i]['cell'] ?? '';
                if ($cellA < $cellB) return -1;
                if ($cellA > $cellB) return 1;
            }
            return 0; // If all first 9 cells are equal
        });

        // склеим колонки
        // Merge rows based on consecutive identical 'system' values
        for ($col = 0; $col <= 6; $col++) {
            $count = 1; // Count of consecutive rows
            for ($row = 1; $row < count($grid); $row++) {
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
}
