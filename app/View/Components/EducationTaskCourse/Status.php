<?php

namespace App\View\Components\EducationTaskCourse;

use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use Illuminate\View\Component;
use function view;

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
    public function __construct(EducationTaskCourse $course, $font = 14)
    {
        $status = $course->status;

        $this->status_color = EducationTaskCourse::STATUS_DATA[$status]['color']['badge'] ?? null;
        $this->status_name = EducationTaskCourse::STATUS_DATA[$status]['name'] ?? null;
        $this->font = $font;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task_course.status');
    }
}
