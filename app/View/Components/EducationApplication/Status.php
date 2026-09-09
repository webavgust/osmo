<?php

namespace App\View\Components\EducationApplication;

use App\Modules\Pub\EducationApplication\Models\EducationApplication;
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
    public function __construct(EducationApplication $task, $font = 14)
    {
        $status = $task->status;
        $this->status_color = EducationApplication::STATUS_DATA[$status]['color']['badge_text'] ?? EducationApplication::STATUS_DATA[$status]['color']['badge'] ?? null;
        $this->status_name = EducationApplication::STATUS_DATA[$status]['name'] ?? null;
        $this->font = $font;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_application.status');
    }
}
