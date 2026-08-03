<?php

namespace App\Modules\Pub\Proposal\Controllers\Api;

use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalGrade;
use App\Modules\Pub\Proposal\Models\ProposalType;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Requests\ListFilterRequest;
use App\Modules\Pub\Proposal\Requests\ProposalRequest;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use App\Modules\Pub\User\Models\User;
use Illuminate\Http\Request;

class ApiProposalController
{
    public $repo;
    public function __construct()
    {
        $this->repo = new ProposalRepository();
        $this->service = new ProposalService();
    }

    public function company(Request $request)
    {
        $company_id = $request->input('company');
        $ret = [];
        if($company_id > 0) {
            $company = CompanyRepository::get($company_id);
            $ar = $this->repo->getForCompany($company);
            foreach($ar as $once) {
                $ret[] = [
                    'id' => $once->group,
                    'text' => $once->name
                ];
            }

        } else {
            $ar = $this->repo->getAll();
            foreach($ar as $once) {
                $ret[] = [
                    'id' => $once->id,
                    'text' => $once->name_number,
                    'company' => $once->company_id
                ];
            }
        }


        return ['data' => $ret];
    }
    public function list_table(Request $request, User $manager = null)
    {
        $data = $this->service->tableDefault(
            params: $request->only(['_token', 'sort', 'order', 'search', 'limit', 'offset']),
            manager: $manager
        );

        return response()->json([
            "total" => $data['count_filter'],
            "totalNotFiltered" => $data['count'],
            "rows" => $data['rows'],
            "temp" => $data,
        ]);
    }




    public function store(ProposalRequest $request)
    {
        $proposal = ProposalRepository::create($request);

        return ['result' => 'success', 'url' => route('proposal.detail', [$proposal, $proposal->iteration])];
    }


    public function update(ProposalRequest $request, Proposal $proposal, int $iteration)
    {
        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if(empty($proposal)) abort(404);

        $proposal = ProposalRepository::update(request: $request, proposal: $proposal);

        return ['result' => 'success', 'url' => route('proposal.detail', [$proposal, $proposal->iteration])];
    }

    public function delete(Proposal $proposal, int $iteration)
    {
        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if(empty($proposal)) abort(404);
        ProposalRepository::delete($proposal);

        return ['result' => 'success'];
    }


    public function convert(Request $request, Proposal $proposal, int $iteration)
    {
        $request->validate([
            'name_alt' => 'nullable|string',
            'currency' => 'required|exists:currencies,slug',
            'rate' => 'numeric|min:0.000000001|max:9999999',
        ]);

        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);

        if(empty($proposal)) abort(404);

        $proposal_new = ProposalRepository::convert(request: $request, proposal: $proposal);

        return ['result' => 'success', 'url' => route('proposal.detail', [$proposal_new, $proposal_new->iteration])];
    }

    public function filter(ListFilterRequest $request)
    {
        $service = new ProposalListFilterService($request->_token);
        $rules_count = $service->setFilter($request->validated());

        return response()->json([
            'result' => 'success',
            'rules_count' => $rules_count
        ]);
    }


    public function filterRemove(ListFilterRequest $request)
    {
        $service = new ProposalListFilterService($request->_token);
        $service->clearFilter();

        return response()->json([
            'result' => 'success'
        ]);
    }


}
