<?php

namespace App\View\Components\Visit;

use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\Visit\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

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
    public function __construct(Visit $visit, $font = 14)
    {
        $status = $visit->status;
        $this->status_color = Visit::STATUS_DATA[$status]['color']['badge'];
        $this->status_name = Visit::STATUS_DATA[$status]['name'];
        $this->font = $font;

    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.visit.status');
    }
}
