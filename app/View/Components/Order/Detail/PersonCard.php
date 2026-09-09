<?php

namespace App\View\Components\Order\Detail;

use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\View\Component;

class PersonCard extends Component
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
    public function __construct(User $person = null, $badge, $color, $type = null, $curator = null)
    {
        $this->person = $person;
        $this->badge = $badge;
        $this->color = $color;
        $this->curator = $curator;
        $this->type = $type;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.order.detail.person_card');
    }
}
