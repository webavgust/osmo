<?php

namespace App\View\Components\OrderTaskObject;

use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject;
use App\Modules\Pub\OrderTaskPoint\Models\OrderTaskPoint;
use Illuminate\View\Component;
use function view;

class BadgeDirection extends Component
{
    public OrderTaskObject $object;
    public array $data;
    public string $direction;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(OrderTaskObject $object)
    {
        $this->object = $object;
        $this->direction = $object->getDirection();
        $this->data = OrderTaskObject::DIRECTION_DATA[$this->direction];
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.order_task_object.badge-direction', [
            'object' => $this->object,
            'data' => $this->data,
            'direction' => $this->direction,
        ]);
    }
}
