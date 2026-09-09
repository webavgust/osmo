<?php

namespace App\Modules\Pub\LicenseKey\Repository;

use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecificationScenario\Models\ContractSpecificationScenario;
use App\Modules\Pub\Organization\Repositories\OrganizationRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use Illuminate\Support\Str;

class LicenseKeyRepository
{

    public static function create(\App\Modules\Pub\Contract\Models\Contract $contract, array $data)
    {
        // создадим договор
        $spec = ContractSpecification::create([
            'name' => $data['name'],
            'amount' => (double)$data['amount'],
            'closed_at' => $data['closed_at'] ?? null,
        ])
        ->contract()->associate($contract)
        ->save();

        return $spec;
    }


    public static function update(ContractSpecification $spec, array $data)
    {
        $spec->update([
            'name' => $data['name'],
            'amount' => (double)$data['amount'],
            'closed_at' => $data['closed_at'],
        ]);

        $spec->contract_specification_scenarios()->delete();
        if(!empty($data['scenario'])) {
            $sort = 0;
            foreach($data['scenario'] as $uuid => $scenario_id) {
                if($scenario_id > 0) {
                    $scenario = ScenarioRepository::getByID($scenario_id);
                    if(!empty($scenario)) {
                        ContractSpecificationScenario::create([
                            'name' => $data['scenario_manual'][$uuid]
                        ])
                        ->contract_specification()->associate($spec)
                        ->scenario()->associate($scenario)
                        ->save();
                    }

                    $sort += 100;
                } elseif(!empty($data['scenario_manual'][$uuid])) {
                    ContractSpecificationScenario::create([
                        'name' => $data['scenario_manual'][$uuid],
                        'sort' => $sort,
                    ])
                    ->contract_specification()->associate($spec)
                    ->save();

                    $sort += 100;
                }
            }
        }

        return $spec;
    }

    public static function delete(ContractSpecification $spec)
    {
        $spec->delete();
    }
}
