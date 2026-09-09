<?php

namespace App\View\Components\EducationTask\Detail;

use App\Modules\Pub\EducationTask\Models\EducationTask;
use Illuminate\View\Component;
use function view;

class Methodist extends Component
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
        $this->person = $task->methodist;

        if ($task->isRefused()) {
            $this->color = 'danger';
            $this->badge = 'Бывший методист';
        } else {
            $this->color = 'success';
            $this->badge = 'Методист';
        }
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task.detail.methodist', [
            'task' => $this->task,
            'person' => $this->person,
            'color' => $this->color,
        ]);
    }
}
