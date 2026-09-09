<?php

namespace App\View\Components\OrderTask;

use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Status extends Component
{
    public $status_color;
    public $status_name;
    public $font;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(OrderTask $orderTask, $font = 14)
    {
        $status = $orderTask->status;
        $this->status_color = OrderTask::STATUS_COLOR[$status]['badge'];
        $this->status_name = OrderTask::STATUS_LANG[$status];
        $this->font = $font;

    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.order_task.status');
    }
}
