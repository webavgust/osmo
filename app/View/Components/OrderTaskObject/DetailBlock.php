<?php

namespace App\View\Components\OrderTaskObject;

use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject;
use App\Modules\Pub\OrderTaskPoint\Models\OrderTaskPoint;
use Illuminate\View\Component;
use function view;

class DetailBlock extends Component
{
    public $course;
    public $course_type;
    public $short;
    public $selected;
    public $points_count;
    public $has_samplers;
    public $samplers;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(OrderTaskObject $object, $short = false, $selected = null)
    {
        $this->object = $object;
        $this->short = $short;
        $this->selected = $selected;


        $this->points_count = OrderTaskPoint::whereHas('address.object', function($builder) use ($object) {
            $builder->where('id', $object->id);
        })->count();

        $this->has_samplers = $object->hasSamplers();
        $this->samplers = $object->getSamplers();
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.order_task_object.detail_block', [
            'object' => $this->object,
            'progress' => $this->object->getProgress()
        ]);
    }
}
