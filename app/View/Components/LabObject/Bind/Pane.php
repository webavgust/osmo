<?php

namespace App\View\Components\LabObject\Bind;

use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\View\Component;

class Pane extends Component
{
    public $object;
    public $measure;
    public $saved;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($object, $measure, $saved)
    {
        $this->object = $object;
        $this->measure = $measure;
        $this->saved = $saved;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.lab-object.bind.pane');
    }
}
