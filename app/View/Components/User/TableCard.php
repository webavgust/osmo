<?php

namespace App\View\Components\User;

use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class TableCard extends Component
{
    public $user;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.user.table_card');
    }
}
