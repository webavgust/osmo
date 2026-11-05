<?php

namespace App\Modules\Pub\Contract\Controllers\Api;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\Contract\Repositories\ContractRepository;
use App\Modules\Pub\Contract\Services\ContractService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalGrade;
use App\Modules\Pub\Proposal\Models\ProposalType;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Requests\ListFilterRequest;
use App\Modules\Pub\Proposal\Requests\ProposalRequest;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ApiContractController
{
    public function store(Request $request, Partner $partner)
    {
        $request->validate([
            'organization' => 'required|exists:organizations,id',
            'manager' => 'nullable|exists:users,id',
            'type' => 'required|in:' . collect(ContractType::cases())->pluck('value')->join(','),
            'number' => 'nullable',
            'date' => 'nullable|date',
            'cb_signed' => 'nullable|bool',
        ]);

        $data = $request->all();

        return DB::transaction(function () use ($request, $partner) {
            $conflict = ContractService::numberConflict($request->input('number'));
            if ($conflict) return ['result' => 'error', 'message' => $conflict];

            ContractRepository::create(partner: $partner, data: $request->all());

            return ['result' => 'success'];
        });
    }


    public function update(Request $request, Contract $contract)
    {
        $request->validate([
            'organization' => 'required|exists:organizations,id',
            'proposal' => 'nullable',
            'manager' => 'nullable|exists:users,id',
            'type' => 'required|in:' . collect(ContractType::cases())->pluck('value')->join(','),
            'number' => 'nullable',
            'date' => 'nullable|date',
            'cb_signed' => 'nullable|bool',
            'proposal_name' => 'nullable|string',
        ]);

        $data = $request->all();
        if(!empty($data['proposal']) && empty($contract->company->proposals()->find($data['proposal'])))
            abort(404);

        return DB::transaction(function () use ($request, $contract) {
            $conflict = ContractService::numberConflict($request->input('number'), $contract);
            if ($conflict) return ['result' => 'error', 'message' => $conflict];

            ContractRepository::update(contract: $contract, data: $request->all());

            return ['result' => 'success'];
        });
    }

    /**
     * Следующий номер договора для подстановки в форму (patch v46)
     *
     * Номер не резервируется: занятым он становится после сохранения договора.
     *
     * @param Request $request
     * @return array
     */
    public function generateNumber(Request $request)
    {
        $request->validate([
            'type' => 'required|in:' . collect(ContractType::cases())->reject(fn ($type) => $type === ContractType::UNKNOWN)->pluck('value')->join(','),
            'manager' => 'required|exists:users,id',
            'date' => 'nullable|date',
        ]);

        $manager = User::withTrashed()->findOrFail($request->input('manager'));
        if (trim((string) $manager->initials) === '') {
            return ['result' => 'error', 'message' => 'У менеджера не заполнены инициалы'];
        }

        return [
            'result' => 'success',
            'number' => ContractService::generateNumber(
                ContractType::from($request->input('type')),
                $manager,
                $request->filled('date') ? Carbon::parse($request->input('date')) : now(),
            ),
        ];
    }

    public function delete(Contract $contract)
    {
        ContractRepository::delete(contract: $contract);

        return ['result' => 'success'];
    }
}
