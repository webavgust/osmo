<?php

namespace App\Modules\Pub\ContractSpecification\Controllers;

use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationProposal;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus;
use App\Modules\Pub\ContractSpecification\Services\SpecProposalService;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\Organization\Repositories\OrganizationRepository;
use App\Modules\Pub\ProjectConfiguration\Repositories\ProjectConfigurationRepository;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class ContractSpecificationController extends Controller
{
    // BOXES
    public function box_add(Contract $contract, Request $request)
    {
        $template = View::make('pub.contract_specification.boxes.add', [
            'title' => 'Создание спецификации',
            'scenarios' => ScenarioRepository::getAll(),
            'contract' => $contract,
            'statuses' => ContractSpecificationStatus::getStatuses(),
            'currencies' => CurrencyRepository::getAll()
        ]);

        return $template;
    }

    public function box_edit(ContractSpecification $spec)
    {
        $template = View::make('pub.contract_specification.boxes.edit', [
            'title' => 'Редактирование спецификации',
            'scenarios' => ScenarioRepository::getAll(),
            'spec' => $spec,
            'statuses' => ContractSpecificationStatus::getStatuses(),
            'currencies' => CurrencyRepository::getAll()
        ]);

        return $template;
    }

    public function box_project_configuration(ContractSpecification $spec)
    {
        $template = View::make('pub.contract_specification.boxes.project_configuration', [
            'title' => 'Прикрепление конфигурации',
            'configurations' => ProjectConfigurationRepository::getAvailable($spec),
            'spec' => $spec,
        ]);

        return $template;
    }

    /**
     * Попап прикрепления КП к спецификации (patch v16).
     *
     * Показываются только КП компании этой спецификации, и только те, где есть
     * блок, соответствующий типу рамочного договора.
     *
     * @param ContractSpecification $spec
     * @return \Illuminate\Contracts\View\View
     */
    public function box_proposal(ContractSpecification $spec)
    {
        $proposals = SpecProposalService::available($spec);
        $attached = SpecProposalService::attached($spec);

        // где ещё лежат эти КП — чтобы менеджер видел, что прикрепляет повторно
        $used = ContractSpecificationProposal::whereIn('proposal_group', $proposals->pluck('group'))
            ->with(['specification.contract'])
            ->get()
            ->groupBy('proposal_group');

        return View::make('pub.contract_specification.boxes.proposal', [
            'title' => 'Прикрепление КП',
            'spec' => $spec,
            'block' => SpecProposalService::block($spec),
            'proposals' => $proposals,
            'attached' => $attached,
            'links' => SpecProposalService::links($spec)->keyBy('proposal_group'),
            'used' => $used,
        ]);
    }
}
