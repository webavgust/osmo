<?php

namespace App\Modules\Pub\Contract\Controllers;

use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\Organization\Repositories\OrganizationRepository;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class ContractController extends Controller
{
    // BOXES
    public function box_add(Partner $partner, Request $request)
    {
        $template = View::make('pub.contract.boxes.add', [
            'title' => 'Создание договора',
            'partner' => $partner,
            'organizations' => OrganizationRepository::getAll(),
            'prefixes' => ContractType::getPrefixes(),
        ]);

        return $template;
    }

    public function box_edit(Contract $contract)
    {
        $template = View::make('pub.contract.boxes.edit', [
            'title' => 'Редактирование договора',
            'contract' => $contract,
            'organizations' => OrganizationRepository::getAll(),
        ]);

        return $template;
    }

}
