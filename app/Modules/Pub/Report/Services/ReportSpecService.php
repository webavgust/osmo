<?php

namespace App\Modules\Pub\Report\Services;

use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus;
use App\Modules\Pub\ContractSpecification\Repository\ContractSpecificationRepository;

class ReportSpecService
{
    public static function specs(string $mode = 'filtered')
    {
        //    Партнер
        // -> клиент
        // -> спецификация
        // -> конфигурация
        // -> состав

        $builder = ContractSpecification::where('id', '>', 0)
            ->whereNotIn('status', [ContractSpecificationStatus::CANCELED])
            ->whereHas('contract.organization', function ($query) {
                $query->where('id', 1);
            })
            ->whereHas('contract', function($query) {
                $query->where('type', ContractType::LICENSE);
            })
        ;

        if($mode == 'filtered') {
            $builder->havingNull("disabled");
            $builder->selectRaw("*, JSON_EXTRACT(`report_data`, '$.\"specs\".\"disabled\"') AS disabled");
        }
        $specs = $builder->get();
        $grid = [];

        $i = 0;
        foreach($specs as $spec) {
            $lines = max(1, $spec->project_configurations->count(), $spec->contract_specification_scenarios->count());
            for($neuro_i = 0; $neuro_i < $lines; $neuro_i++) {
                $grid[$i] = []; // строка

                $grid[$i][0] = ['rowspan' => 1, 'cell' => $spec->company->partner->name, 'system' => $spec->company->partner->id];
                $grid[$i][1] = ['rowspan' => 1, 'cell' => $spec->company->name, 'system' => $spec->company->id];

//                $grid[$i][2] = ['rowspan' => 1, 'cell' => $spec->contract->type ?? '', 'system' => $spec->company->id . ':' . $spec->contract->id ];
                $grid[$i][2] = ['rowspan' => 1, 'cell' => $spec->contract->number ?? '', 'system' => $spec->company->id . ':' . $spec->contract->id, 'org' => $spec->contract->organization];



                $grid[$i][3] = ['rowspan' => 1, 'cell' => $spec->name, 'system' => $spec->id, 'instance' => $spec];

                if(!empty($spec->project_configurations[$neuro_i])) {
                    $conf = $spec->project_configurations[$neuro_i];
                    $grid[$i][4] = ['rowspan' => 1, 'cell' => $conf->number, 'system' => $spec->id . ':' . $neuro_i];
                } else {
                    $grid[$i][4] = ['rowspan' => 1, 'cell' => '', 'system' => $spec->id ];
                }


                if(!empty($spec->contract_specification_scenarios[$neuro_i])) {
                    $scenario = $spec->contract_specification_scenarios[$neuro_i]->scenario?->name ?? $spec->contract_specification_scenarios[$neuro_i]->name;
                    $grid[$i][5] = ['rowspan' => 1, 'cell' => $scenario, 'system' => $spec->id . ':' . $neuro_i, 'handle' => empty($spec->contract_specification_scenarios[$neuro_i]->scenario)];
                } else {
                    $grid[$i][5] = ['rowspan' => 1, 'cell' => '', 'system' => $spec->id . ':' . $neuro_i ];
                }

                $i++;
            }
        }

        // Sort the grid by 'cell' values of the first 9 columns
        usort($grid, function($a, $b) {
            for ($i = 0; $i <= 4; $i++) {

                $cellA = $a[$i]['cell'] ?? '';
                $cellB = $b[$i]['cell'] ?? '';

                if ($cellA < $cellB) return -1;
                if ($cellA > $cellB) return 1;
            }
            return 0; // If all first 9 cells are equal
        });


        // склеим колонки
        // Merge rows based on consecutive identical 'system' values
        for ($col = 0; $col <= 4; $col++) {
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

    public static function setActive(mixed $active)
    {
        $matrix = ReportSpecService::specs('all');

        foreach(array_column($matrix, 2) as $row) {
            if(empty($row['instance']))  continue;

            $spec = $row['instance'];
            $row_report_data = $spec->report_data ?? [];

            // активно, убираем флаг
            if(!empty($active[$spec->id]) && $active[$spec->id]) {
                if(!empty($row_report_data['specs']['disabled']))
                    unset($row_report_data['specs']['disabled']);
            } else {
                if(empty($row_report_data['specs'])) {
                    $row_report_data['specs'] = [];
                }
                $row_report_data['specs']['disabled'] = 1;
            }

            $spec->update(['report_data' => $row_report_data]);
        }

    }
}
