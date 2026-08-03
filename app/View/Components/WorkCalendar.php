<?php

namespace App\View\Components;

use Carbon\Carbon;
use Illuminate\View\Component;

class WorkCalendar extends Component
{
    public $data; // значения для ячеек дней
    public $date; // экземляр карбона для ячейки

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($month, $year, $dates, $custom = [])
    {
        $carbon = Carbon::createFromFormat('m-Y', $month . '-' . $year)->firstOfMonth();
        $this->date = clone $carbon;
        $this->saved = $dates;
        $this->custom = $custom;

        $left_fill = $carbon->dayOfWeekIso;

        $carbon->subDay();
        do {
            $carbon->addDay();
            $arData[$carbon->week][$carbon->dayOfWeekIso] = [
                "num" => $carbon->day,
                "date" => $carbon->format('Y-m-d'),
                "week_num" => $carbon->dayOfWeekIso,
                "day_num" => $carbon->dayOfYear,
                "checked" => in_array($carbon->format('Y-m-d'), $dates),
                "custom" => !empty($this->custom[$carbon->format('Y-m-d')])
            ];
        } while (!$carbon->isLastOfMonth());
        $this->data = $arData;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.work-calendar', ['data' => $this->data]);
    }
}
