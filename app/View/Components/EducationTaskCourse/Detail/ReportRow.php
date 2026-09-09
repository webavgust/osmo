<?php

namespace App\View\Components\EducationTaskCourse\Detail;

use App\Modules\Pub\Client\Models\Client;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\Report\Models\Report;
use Illuminate\View\Component;
use function view;

class ReportRow extends Component
{
    public $task_course;
    public $report;
    public $file;
    public $uploaded;


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(EducationTaskCourse $taskCourse, Report $report)
    {
        $this->task_course = $taskCourse;
        $this->report = $report;
        $this->file = $taskCourse->files()->where('target_block', $report->slug)->first();
        $this->uploaded = $taskCourse->files()->where('target_block', $report->slug . '-scan')->orderByDesc('id')->first();
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task_course.detail.report_row', [
            'task_course' => $this->task_course,
            'report' => $this->report,
            'file' => $this->file,
            'uploaded' => $this->uploaded,
        ]);
    }
}
