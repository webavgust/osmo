<?php
namespace App\Modules\Pub\ProposalPdfTemplate\Repositories;


use App\Modules\Pub\Proposal\Models\Proposal;

class ProposalPdfTemplateRepository
{
    public function store(Proposal $proposal, array $data)
    {
        dd($data['html']);
    }
}
