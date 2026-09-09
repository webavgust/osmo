<?php

namespace App\View\Components\StudyLesson;

use App\Modules\Pub\Course\Models\Course;
use App\Modules\Pub\Teacher\Repositories\TeacherRepository;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class GenerateLessonBlank extends Component
{
    public $code;
    public $duration;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($num, $duration, $classes)
    {
        $this->code = Str::random(8);
        $this->num = $num;
        $this->duration = $duration;
        $this->classes = $classes;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.study_lesson.generate_lesson_blank', [
            'num' => $this->num,
            'code' => $this->code,
            'duration' => $this->duration,
            'classes' => $this->classes,
        ]);
    }
}
