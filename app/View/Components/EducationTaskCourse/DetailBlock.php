<?php

namespace App\View\Components\EducationTaskCourse;

use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use Illuminate\View\Component;
use function view;

class DetailBlock extends Component
{
    public $course;
    public $course_type;
    public $short;
    public $selected;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(EducationTaskCourse $course, $short = false, $selected = null)
    {
        $this->course = $course;
        $this->course_type = EducationTaskCourse::TYPES[$course->course_type]['name'];
        $this->short = $short;
        $this->selected = $selected;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task_course.detail_block', [
            'course' => $this->course,
            'course_type' => $this->course_type
        ]);
    }
}
