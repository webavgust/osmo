<?php

namespace App\View\Components\EducationApplication;

use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use Illuminate\View\Component;
use function view;

class Actions extends Component
{
    public $education_application;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(EducationApplication $orderTask)
    {
        $this->education_application = $orderTask;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_application.actions');
    }
}
