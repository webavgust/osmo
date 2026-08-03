<?php

namespace App\Modules\Pub\LicenseKey\Repository;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecificationScenario\Models\ContractSpecificationScenario;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\Organization\Repositories\OrganizationRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use Illuminate\Support\Str;

class LicenseKeyRepository
{

    public static function create(Company $company, array $data)
    {
        // создадим договор
        $key = LicenseKey::create([
            'active' => $data['active'] ?? false,
            'key' => $data['key'],
            'active_from' => $data['active_from'],
            'active_to' => $data['active_to'],
        ])
        ->company()->associate($company)
        ;


        if (!empty($data['specification'])) {
            $spec = $company->specifications->where('id', $data['specification'])->first();
            if(!empty($spec))
                $key->specification()->associate($spec);
        }


        $key->save();

        return $key;
    }


    public static function update(LicenseKey $key, array $data)
    {
        $key->update([
            'active' => $data['active'] ?? false,
            'key' => $data['key'],
            'active_from' => $data['active_from'],
            'active_to' => $data['active_to'],
        ]);

        $spec = !empty($data['specification']) ? $key->company->specifications->where('id', $data['specification'])->first() : null;
        $key->specification()->associate($spec)->save();


        return $spec;
    }

    public static function delete(LicenseKey $key)
    {
        $key->delete();
    }
}
