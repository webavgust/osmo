<?php

namespace App\View\Components\EducationTaskCourse;

use App\Modules\Pub\Document\Models\Document;
use App\Modules\Pub\Document\Repositories\DocumentRepository;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\EducationTaskCourseWork\Models\EducationTaskCourseWork;
use App\Modules\Pub\Work\Repositories\WorkRepository;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use function view;

class WorkRow extends Component
{
    public $rows;
    public $uid;
    public $work;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(EducationTaskCourseWork $work = null)
    {
        $this->work = $work;
        $this->rows = WorkRepository::getAll();
        $this->uid = $work->id ?? Str::random(16);
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task_course.work_row', [
            'rows' => $this->rows,
            'uid' => $this->uid,
            'work' => $this->work,
        ]);
    }
}
