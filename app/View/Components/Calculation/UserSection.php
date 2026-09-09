<?php

namespace App\View\Components\Calculation;

use App\Modules\Pub\Course\Models\Course;
use App\Modules\Pub\Teacher\Repositories\TeacherRepository;
use App\Modules\Pub\User\Repositories\UserRepository;
use Illuminate\View\Component;

class UserSection extends Component
{
    public $type;
    public $userId;
    public $rows;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($type, $userId, $rows)
    {
        $amounts = 0;
        $corrections = 0;
        $this->type = $type;
        $this->user = UserRepository::getById($userId);
        $this->rows = $rows;


        foreach ($this->rows as $row) {
            if ($row->is_correction) {
                $corrections += $row->amount;
            } else {
                $amounts += $row->amount;
            }
        }
        $this->amounts = $amounts;
        $this->corrections = $corrections;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.calculation.user_section', [
            'type' => $this->type,
            'user' => $this->user,
            'rows' => $this->rows,
            'amounts' => $this->amounts,
            'corrections' => $this->corrections,
        ]);
    }
}
