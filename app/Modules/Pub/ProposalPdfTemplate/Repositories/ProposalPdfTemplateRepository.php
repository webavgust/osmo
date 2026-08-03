<?php
namespace App\Modules\Pub\ProposalPdfTemplate\Repositories;


use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalPdfTemplate\Models\ProposalPdfTemplate;

class ProposalPdfTemplateRepository
{
    public static function store(Proposal $proposal, array $data)
    {
        $template = ProposalPdfTemplate::make([
            'name' => $data['name'],
            'html' => $data['html']
        ])
        ->proposal()->associate($proposal)
        ->creator()->associate(auth()->user())
        ->save();

    }
}

