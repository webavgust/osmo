<?php

namespace App\View\Components\OrderTask;

use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject;
use App\Modules\Pub\OrderTaskPoint\Models\OrderTaskPoint;
use Illuminate\View\Component;
use function view;

class BadgeDirection extends Component
{
    public OrderTask $task;
    public $data;
    public array $directions;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(OrderTask $task)
    {
        $this->task = $task;
        $this->directions = $task->getDirections();
        foreach($this->directions as $direction) {
            $this->data[$direction] = OrderTaskObject::DIRECTION_DATA[$direction];
        }

    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.order_task.badge-direction', [
            'task' => $this->task,
            'data' => $this->data,
            'directions' => $this->directions,
        ]);
    }
}
