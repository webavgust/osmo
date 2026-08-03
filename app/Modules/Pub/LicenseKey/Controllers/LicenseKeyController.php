<?php

namespace App\Modules\Pub\LicenseKey\Controllers;

use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\Organization\Repositories\OrganizationRepository;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class LicenseKeyController extends Controller
{
    // BOXES
    public function box_add(Company $company, Request $request)
    {

        $specs = $company->specifications;
        $template = View::make('pub.license_key.boxes.add', [
            'title' => 'Прикрепление ключа',
            'company' => $company,
            'specs' => $specs,
        ]);

        return $template;
    }

    public function box_edit(LicenseKey $license_key)
    {
        $specs = $license_key->company->specifications;
        $template = View::make('pub.license_key.boxes.edit', [
            'title' => 'Редактирование ключа',
            'key' => $license_key,
            'specs' => $specs,
        ]);

        return $template;
    }

}
