<?php

namespace App\View\Components\Visit;

use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\Visit\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class OrderTaskObjectButton extends Component
{
    public $visit;
    public $preset;
    public $icon;
    public $color;
    public $box;
    public $href;
    public $button;
    public $button_add;
    public $is_outline;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Visit $visit)
    {
        $this->visit = $visit;
        $this->preset = \App\Modules\Pub\Visit\Models\Visit::STATUS_DATA[$visit->status];
        $this->icon = $this->preset['icon'];
        $this->color = $this->preset['color']['button'];
        $this->is_outline = $this->preset['type']=='outline';
        $this->button = $this->preset['button'] ?? null;
        $this->button_add = [];

        switch($visit->status) {
            case Visit::STATUS_CREATED:
                $this->box = route("visit.box_to_working", $visit);
                break;
            case Visit::STATUS_WORKING:
            case Visit::STATUS_EXPIRED:
            case Visit::STATUS_PROCESSING:
                if($visit->canAddSamplerWork()) {
                    $this->button_add['href'] = route("visit.fill", $visit);
                    if($visit->hasSamplerWorkDraft()) {
                        $this->button_add['name'] = 'Есть черновик';
                    } else {
                        $this->button_add['name'] = 'Внести пробы';
                    }
                    if(is_admin())
                        $this->button_add['name'] = '<div>' . $this->button_add['name'] . ' <i class="fa-light fa-unlock-keyhole" icon="fa-unlock-keyhole"></i> админ</div>';
                }

                $this->box = route("visit.box_view_detail", $visit);
                $this->button = 'Просмотреть пробы';

                break;
            case Visit::STATUS_ASSETTED:
            case Visit::STATUS_ANALYTIC_FINISHED:
            case Visit::STATUS_FINISHED:
                $this->box = route("visit.box_view_detail", $visit);
                if($visit->onlyViewAssets())
                    $this->button = 'Просмотреть пробы';

                break;
        }
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.visit.order_task_object_button');
    }
}
