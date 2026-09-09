<?php

namespace App\View\Components\EducationApplication\Create;

use App\Modules\Pub\LabObject\Models\LabObject;
use Illuminate\View\Component;
use function view;

class ObjectRow extends Component
{
    public $lab_objects;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(\Illuminate\Database\Eloquent\Collection $lab_objects)
    {
        //
        $this->lab_objects = $lab_objects;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_application.create.object_row');
    }
}
