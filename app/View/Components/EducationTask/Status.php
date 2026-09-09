<?php

namespace App\View\Components\EducationTask;

use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use Illuminate\View\Component;
use function view;

class Status extends Component
{
    public $status_color;
    public $status_name;
    public $font;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(OrderTask $task, $font = 14)
    {
        $status = $task->status;
        $this->status_color = OrderTask::STATUS_COLOR[$status]['color']['badge'] ?? OrderTask::STATUS_COLOR[$status]['color']['button'] ?? null;
        $this->status_name = OrderTask::STATUS_LANG[$status] ?? null;
        $this->font = $font;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task.status');
    }
}
