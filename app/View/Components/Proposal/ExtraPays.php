<?php

namespace App\View\Components\Proposal;

use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use Illuminate\View\Component;

class ExtraPays extends Component
{
    public $proposal;

    public function __construct(ProposalVariant $variant)
    {
        $this->variant = $variant;

    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.proposal.extra-pays', ['variant' => $this->variant]);
    }
}
