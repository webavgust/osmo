<?php

namespace App\View\Components\Log;

use App\Modules\Pub\Course\Models\Course;
use App\Modules\Pub\Log\Repositories\LogRepository;
use App\Modules\Pub\Teacher\Repositories\TeacherRepository;
use Carbon\Carbon;
use Illuminate\View\Component;

class Story extends Component
{
    public $rows;

    public function __construct(public Carbon $date)
    {
        $this->rows = LogRepository::getForDay($date);
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.log.story', [
            'date' => $this->date,
            'rows' => $this->rows,
        ]);
    }
}
