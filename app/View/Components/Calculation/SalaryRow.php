<?php

namespace App\View\Components\Calculation;

use App\Modules\Pub\Course\Models\Course;
use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\Teacher\Repositories\TeacherRepository;
use App\Modules\Pub\User\Repositories\UserRepository;
use Illuminate\View\Component;

class SalaryRow extends Component
{
    public $type;
    public $salary;
    public $target;
    public $link;
    public $target_name;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($type, $salary)
    {
        $this->type = $type;
        $this->salary = $salary;

        switch ($type) {
            case 'supervisor':
            case 'tender':
                $this->target = EducationTask::find($salary->target_id);
                if ($this->target->hasAccess()) {
                    $this->link = route('education-task.detail', $this->target);
                    $this->target_name = 'Техническое задание № ' . $this->target->id;
                }
                break;
            case 'methodist':
                $this->target = EducationTaskCourse::find($salary->target_id);
                if ($this->target->canViewDetail()) {
                    $this->link = route('education-task-course.detail', $this->target);
                    $this->target_name = 'Программа обучения № ' . $this->target->id;
                }
                break;
        }
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.calculation.salary_row', [
            'type' => $this->type,
            'salary' => $this->salary,
            'target' => $this->target,
            'link' => $this->link,
            'target_name' => $this->target_name,
        ]);
    }
}
