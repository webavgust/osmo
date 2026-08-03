<?php

namespace App\View\Components\User\Cards;

use App\Models\ModuleModel;
use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\View\Component;

class Curator extends Component
{
    public $type;
    public $color;
    public $badge;
    public $person;
    public $curator;
    public $instance;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(User $person, ModuleModel $instance, $badge, $color)
    {
        $this->person = $person;
        $this->badge = $badge;
        $this->color = $color;
        $this->instance = $instance;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.user.cards.curator');
    }
}
