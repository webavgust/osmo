<?php

namespace App\View\Components\EducationApplication\Cell;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use function auth;
use function view;

class Agreementer extends Component
{
    public $task;
    public $agreed;
    public $comment;
    public $owner = false;

    public function __construct($task = null)
    {

        $this->task = $task;

        if (!empty($task) && !empty($task->agreement) && $task->agreement->users->contains(auth()->id())) {
            $this->owner = true;
            $pivot = $task->agreement->users()->where('id', '=', auth()->id())->first()->pivot;
            $this->agreed = $pivot->agreed;
            $this->comment = $pivot->comment;
        }
    }

    public function render(): View
    {
        return view('components.order-task.cell.agreementer');
    }
}
