<?php

namespace App\View\Components\EducationTaskCourse;

use App\Modules\Pub\Document\Models\Document;
use App\Modules\Pub\Document\Repositories\DocumentRepository;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\EducationTaskCourseService\Models\EducationTaskCourseService;
use App\Modules\Pub\EducationTaskCourseWork\Models\EducationTaskCourseWork;
use App\Modules\Pub\Service\Repositories\ServiceRepository;
use App\Modules\Pub\Work\Repositories\WorkRepository;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use function view;

class ServiceRow extends Component
{
    public $rows;
    public $uid;
    public $service;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(EducationTaskCourseService $service = null)
    {
        $this->service = $service;
        $this->rows = ServiceRepository::getAll();
        $this->uid = $service->id ?? Str::random(16);
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task_course.service_row', [
            'rows' => $this->rows,
            'uid' => $this->uid,
            'service' => $this->service,
        ]);
    }
}
