<?php

namespace App\View\Components\EducationApplicationCourse;

use App\Modules\Pub\EducationApplicationCourse\Models\EducationApplicationCourse;
use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use Illuminate\View\Component;
use function view;

class DetailBlock extends Component
{
    public $course;
    public $course_type;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(EducationApplicationCourse $course)
    {
        $this->course = $course;
        $this->course_type = EducationApplicationCourse::TYPES[$course->course_type]['name'];
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_application_course.detail_block', [
            'course' => $this->course,
            'course_type' => $this->course_type
        ]);
    }
}
