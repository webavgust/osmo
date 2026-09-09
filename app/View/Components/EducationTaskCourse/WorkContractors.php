<?php

namespace App\View\Components\EducationTaskCourse;

use App\Modules\Pub\Work\Models\Work;
use Illuminate\View\Component;
use function view;

class WorkContractors extends Component
{
    public $uid;
    public $contractors;
    public $selected;
    public $costs;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($uid, $workId, $selected = null)
    {
        $work = Work::find($workId);
        $this->uid = $uid;
        $this->contractors = $work->contractors ?? [];
        $this->selected = $selected;

        if (!empty($work->cost)) {
            $arCost = collect([0 => $work->cost]);
        } else {
            $arCost = collect([0 => 0]);
        }
        if (!empty($this->contractors)) {
            $this->contractors->map(function ($item) use (&$arCost) {
                $item->name .= ' (' . $item->pivot['cost'] . ')';
                $arCost[$item->id] = $item->pivot['cost'];
            });
            $this->costs = $arCost->toArray();
        }
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task_course.work_contractors', [
            'contractors' => $this->contractors,
            'selected' => $this->selected,
            'uid' => $this->uid,
            'costs' => $this->costs
        ]);
    }
}
