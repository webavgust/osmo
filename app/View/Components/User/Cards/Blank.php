<?php

namespace App\View\Components\User\Cards;

use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\View\Component;

class Blank extends Component
{
    public $type;
    public $color;
    public $badge;
    public $person;
    public $curator;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(User $person = null, $badge, $color)
    {
        $this->person = $person;
        $this->badge = $badge;
        $this->color = $color;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.user.cards.blank');
    }
}
