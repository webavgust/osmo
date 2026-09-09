<?php

namespace App\View\Components\OrderTask\Create;

use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\LabObject\Repository\LabObjectRepository;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class ObjectRow extends Component
{
    public $lab_objects;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        $this->lab_objects = LabObjectRepository::getLast();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.order_task.create.object_row');
    }
}
