<?php

namespace App\Modules\Pub\ProposalPdfTemplate\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use Illuminate\Support\Facades\View;

class ProposalPdfTemplateController extends Controller
{
    public function box_templates(Proposal $proposal, int $iteration = 1)
    {
        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if(empty($proposal)) abort(404);

        $template = View::make('pub.proposal_pdf_template.box.templates', [
            'title' => 'Список шаблонов для КП',
            'proposal' => $proposal,
        ]);
        return $template;
    }
}
