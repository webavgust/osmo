<?php

namespace App\Modules\Pub\ProposalVariantExtraPay\Services;

use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalVariantExtraPay\Models\ProposalVariantExtraPay;

class ProposalVariantExtraPayService
{


    public static function recalc(ProposalVariant $variant)
    {
        $data = ['percent' => []];
        $variant->extra_pays->each(function($once) use (&$data) {
            $data['percent'][] = [
                'name' => $once->name,
                'type' => $once->block,
                'amount' => $once->percent,
            ];
        });

        static::create($variant, $data);
    }

    public static function create(ProposalVariant $variant, array $data)
    {
        $variant->extra_pays()->delete();

        $base_software = $variant->neuro_cost_total + $variant->platform_cost_total + $variant->neuro_nds_cost_total + $variant->platform_nds_cost_total;
        $base_work = $variant->work_cost_total + $variant->work_nds_cost_total;

        $sort = 0;
        foreach($data['percent'] as $once) {
            $type = $once['type'];
            $percent = (float)$once['amount'];
            if(!$percent) continue;

            $sort += 100;
            $base = $value = 0;

            $start_software = $base_software;
            $start_work = $base_work;

            switch ($type) {
                case 'software':
                    $base += $base_software;
                    $value = round($base_software / 100 * $percent, 2);
                    $base_software += $value;
                    break;
                case 'work':
                    $base += $base_work;
                    $value = round($base_work / 100 * $percent, 2);
                    $base_work += $value;
                    break;
                case 'all':
                    $base = $base_software + $base_work;
                    $value1 = round($base_software / 100 * $percent, 2);
                    $base_software += $value1;

                    $value2 = round($base_work / 100 * $percent, 2);
                    $base_work += $value2;

                    $value = $value1 + $value2;
                    break;
            }

            $extra = ProposalVariantExtraPay::create([
                'name' => $once['name'],
                'block' => $once['type'],
                'type' => 'percent',
                'percent' => $percent,
                'value' => $value,              // надбавка
                'base' => $base,                // от какой суммы считали

                'software_start' => $start_software,
                'software_end' => $base_software,
                'work_start' => $start_work,
                'work_end' => $base_work,

                'total' => $base_software + $base_work,
                'currency' => $variant->proposal->currency_slug,
                'sort' => $sort,
            ]);
            $extra->variant()->associate($variant)->save();
        }
    }
}
