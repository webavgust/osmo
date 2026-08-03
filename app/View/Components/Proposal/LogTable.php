<?php

namespace App\View\Components\Proposal;

use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Reminder\Models\Reminder;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class LogTable extends Component
{

    public $proposal;

    public function __construct(Proposal $proposal)
    {
        $this->proposal = $proposal;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.proposal.log_table', ['proposal' => $this->proposal]);
    }
}
