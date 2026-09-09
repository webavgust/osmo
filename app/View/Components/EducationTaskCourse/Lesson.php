<?php

namespace App\View\Components\EducationTaskCourse;

use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\StudyLesson\Models\StudyLesson;
use Carbon\Carbon;
use Illuminate\View\Component;
use function view;

class Lesson extends Component
{
    public $lesson;
    public $is_prev;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(StudyLesson $lesson)
    {
        $this->lesson = $lesson;
        if ($lesson->start_at->lessThan(Carbon::now()))
            $this->is_prev = true;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task_course.lesson', [
            'lesson' => $this->lesson,
            'is_prev' => $this->is_prev
        ]);
    }
}
