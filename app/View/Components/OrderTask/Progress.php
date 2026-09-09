<?php

namespace App\View\Components\OrderTask;

use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\Visit\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Progress extends Component
{
    public $blocks;
    public $blank;
    public $all;
    public $short;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Collection $progress, bool $short = false)
    {
        $arData = [
            'left' => ['color' => 'warning', 'name_short' => 'Осталось'],
            'visit' => ['color' => 'info', 'name_short' => 'Создан выезд'],
            'asseted' => ['color' => 'success', 'name_short' => 'Внесены пробы'],
            'analyzed' => ['color' => 'primary', 'name_short' => 'Обработано'],
            'finished' => ['color' => 'secondary', 'name_short' => 'Завершено'],
        ];
        $chunk_size = (!empty($progress['all']) && $progress['all'] > 0) ? round(100 / $progress['all'], 2) : 100;
        $this->all = $progress['all']  ?? 0;
        $this->blank = !empty($progress) ? (($progress['all'] ?? 0) - $progress->except('all')->sum()) : 100;
        $this->short = $short;


        $progress->forget('all');
        $blocks = [];
        foreach($progress as $visit_status => $count) {
            if(!$count)
                continue;

            $blocks[] = [
                'color' => $arData[$visit_status]['color'],
                'width' => round($chunk_size * $count, 2),
                'count' => $count,
                'percent' => round($chunk_size * $count),
                'name' => $arData[$visit_status]['name_short'],
            ];
        }
        $this->blocks = $blocks;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.order_task.progress');
    }
}
