<?php

namespace App\View\Components\LabObject\Bind;

use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\View\Component;

class Pad extends Component
{
    public $object;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.lab-object.bind.pad', [
            'object' => $this->object
        ]);
    }
}
