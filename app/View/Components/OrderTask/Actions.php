<?php

namespace App\View\Components\OrderTask;

use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Actions extends Component
{
    public $order_task;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(OrderTask $orderTask)
    {
        $this->order_task = $orderTask;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.order_task.actions');
    }
}
