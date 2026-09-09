<?php

namespace App\View\Components\OrderTaskAddress\Summary;

use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\OrderTaskAddress\Models\OrderTaskAddress;
use App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject;
use App\Modules\Pub\OrderTaskPoint\Models\OrderTaskPoint;
use Illuminate\View\Component;
use function view;

class Table extends Component
{

    public $address;
    public $data;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(OrderTaskAddress $address)
    {
        $this->address = $address;
        $arData = collect();
        foreach($address->points as $point) {
            if(empty($arData[$point->id])) $arData[$point->id] = collect();
            foreach($point->measures->flatMap->visit_order_task_measures as $votm) {
                if(empty($arData[$point->id][$votm->order_task_measure->id])) $arData[$point->id][$votm->order_task_measure->id] = collect();
                foreach($votm->samples as $sample) {
                    $arData
                    [$point->id]
                    [$votm->order_task_measure->id]
                    [] = [
                        'container' => $sample->visit_container,
                        'count' => $sample->count,
                        'works' => $sample->sample_works->whereNotNull('finished_at'),
                    ];
                }
            }
        }
        $this->data = $arData;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.order_task_address.summary.table', [
            'address' => $this->address,
            'data' => $this->data,
        ]);
    }
}
