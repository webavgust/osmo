<?php

namespace App\View\Components\Evaluation;

use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use Illuminate\View\Component;
use function view;

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
    public function __construct(Evaluation $evaluation, $font = 14)
    {
        $status = $evaluation->status;
        $this->status_color = Evaluation::STATUS_DATA[$status]['color']['badge_text'] ?? Evaluation::STATUS_DATA[$status]['color']['badge'] ?? null;
        $this->status_name = Evaluation::STATUS_DATA[$status]['name'] ?? null;
        $this->font = $font;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.evaluation.status');
    }
}
