<?php

namespace App\Modules\Pub\ContractSpecification\Repository;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecificationScenario\Models\ContractSpecificationScenario;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\Organization\Repositories\OrganizationRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use Illuminate\Support\Str;

class ContractSpecificationRepository
{

    public static function create(\App\Modules\Pub\Contract\Models\Contract $contract, array $data)
    {
        // создадим договор
        $company = Company::findOrFail($data['company']);

        $spec = ContractSpecification::create([
            'name' => $data['name'],
//            'amount' => (double)$data['amount'],
            'status' => $data['status'],
            'is_signed' => !empty($data['cb_signed']) && $data['cb_signed'] ? 1 : 0,
        ])
        ->contract()->associate($contract)
        ->company()->associate($company)
        ->currency()->associate(CurrencyRepository::get($data['currency']))
        ->save();

        return $spec;
    }


    public static function update(ContractSpecification $spec, array $data)
    {
        $spec
        ->fill([
            'name' => $data['name'],
//            'amount' => (double)$data['amount'],
            'status' => $data['status'],
            'is_signed' => !empty($data['cb_signed']) && $data['cb_signed'] ? 1 : 0,
        ])
        ->company()->associate(Company::findOrFail($data['company']))
        ->contract()->associate(Contract::findOrFail($data['contract']))
        ->currency()->associate(CurrencyRepository::get($data['currency']))
        ->save();

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

    public static function attachProjectConfiguration(ContractSpecification $spec, $configuration)
    {
        $configuration->contract_specification()->associate($spec)->save();
    }

    public static function detachProjectConfiguration(ContractSpecification $spec, $configuration)
    {
        $configuration->contract_specification()->associate(null)->save();
    }

    /**
     * Спецификации компании, сгруппированные по типу договора и договору.
     *
     * Проверка по договору вынесена из проверки по типу: раньше она была вложена
     * внутрь, и у второго договора того же типа ключ не создавался — на карточке
     * компании это падало с «Undefined array key».
     *
     * @param Company $company
     * @return \Illuminate\Support\Collection тип → id договора → [instance, specs]
     */
    public static function getGrouped(Company $company)
    {
        $arRet = collect();

        $company->specifications->each(function($spec) use (&$arRet) {
            // у спецификации может не быть договора — такую пропускаем,
            // иначе падаем на обращении к type
            if(empty($spec->contract)) return;

            if(empty($arRet[$spec->contract->type])) {
                $arRet[$spec->contract->type] = collect();
            }

            if(empty($arRet[$spec->contract->type][$spec->contract->id])) {
                $arRet[$spec->contract->type][$spec->contract->id] = collect([
                    'instance' => $spec->contract,
                    'specs' => collect()
                ]);
            }

            $arRet[$spec->contract->type][$spec->contract->id]['specs']->push($spec);
        });

        return $arRet;
    }
}
