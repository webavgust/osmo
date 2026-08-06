<?php

namespace App\Modules\Pub\ProposalTools\Controllers\Api;

use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\ProposalTools\Services\ProposalCloneService;
use Illuminate\Http\Request;

class ApiProposalToolsController
{
    /**
     * Склонировать КП
     *
     * @param Request $request
     * @param Proposal $proposal
     * @param int $iteration
     * @return array
     */
    public function clone(Request $request, Proposal $proposal, int $iteration)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'number' => 'required|string|max:64',
            'sended_at' => 'nullable|date',
            'company_id' => 'nullable|integer',
            'partner_id' => 'nullable|integer',
            'manager_id' => 'nullable|integer',
        ]);

        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if (empty($proposal)) abort(404);

        try {
            $clone = ProposalCloneService::clone($proposal, [
                'name' => $request->input('name'),
                'number' => $request->input('number'),
                'sended_at' => $request->input('sended_at'),
                'company_id' => $request->input('company_id'),
                'partner_id' => $request->input('partner_id'),
                'manager_id' => $request->input('manager_id'),
            ]);
        } catch (\Throwable $e) {
            return ['result' => 'error', 'message' => 'Не получилось склонировать: ' . $e->getMessage()];
        }

        return [
            'result' => 'success',
            'url' => route('proposal.detail', [$clone, $clone->iteration]),
        ];
    }
}
