<?php

namespace App\View\Components\EducationApplication\Create;

use App\Modules\Pub\Course\Repositories\CourseRepository;
use App\Modules\Pub\EducationApplicationCourse\Models\EducationApplicationCourse;
use App\Modules\Pub\EducationCenter\Repositories\EducationCenterRepository;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\Reminder\Models\Reminder;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class CourseRow extends Component
{

    public $num;
    public $courses;
    public $program_types;
    public $edu_centers;

    public function __construct(EducationApplicationCourse $course = null, $num = null)
    {
        $this->uid = $num ?? Str::uuid()->toString();
        $this->course = $course;
        $this->courses = CourseRepository::getAllWithDuration();
        $this->program_types = EducationApplicationCourse::TYPES;
        $this->edu_centers = EducationCenterRepository::getActive();
    }
    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_application.create.course_row', [
            'courses' => $this->courses,
            'program_types' => $this->program_types,
            'uid' => $this->uid,
            'course' => $this->course,
            'edu_centers' => $this->edu_centers,
        ]);
    }
}
