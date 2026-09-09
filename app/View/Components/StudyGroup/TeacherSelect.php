<?php

namespace App\View\Components\StudyGroup;

use App\Modules\Pub\Course\Models\Course;
use App\Modules\Pub\Teacher\Repositories\TeacherRepository;
use Illuminate\View\Component;

class TeacherSelect extends Component
{
    public $course;
    public $type;
    public $teachers;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($course, $type)
    {
        $this->course = Course::findOrFail($course);
        $this->type = $type;
        $repo = new TeacherRepository();
        $this->teachers = $repo->getFittableTeachers($this->course->id, $this->type);
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.study_group.teacher_select', [
            'course' => $this->course,
            'type' => $this->type,
            'teachers' => $this->teachers
        ]);
    }
}
