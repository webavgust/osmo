<?php

namespace App\View\Components\EducationTask\Detail;

use App\Modules\Pub\EducationTask\Models\EducationTask;
use Illuminate\View\Component;
use function view;

class Supervisor extends Component
{
    public $task;
    public $person;
    public $color;
    public $badge;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(EducationTask $task)
    {
        $this->task = $task;
        $this->person = $task->supervisor;

        if (empty($this->person)) {
            $this->color = 'danger';
        } else {
            $this->color = 'info';
        }
        $this->badge = 'Руководитель';
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task.detail.supervisor', [
            'task' => $this->task,
            'person' => $this->person,
            'color' => $this->color,
        ]);
    }
}
