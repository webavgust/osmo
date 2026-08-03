<?php

namespace App\View\Components\Reminder;

use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\Reminder\Models\Reminder;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Row extends Component
{
    public $remind;
    public $users;
    public $module;
    public $link;
    public $filtered;
    public $sidebar = false;

    public function __construct(Reminder $remind, $filtered = false)
    {
        $this->remind = $remind;
        $this->filtered = $filtered;
        $this->users = $remind->groupUsers();
        if (!empty($remind->target_type)) {
            $object = $remind->target_type::findOrFail($remind->target_id);
            $this->module = $object->getModuleSlug();


            if (!empty(($remind->target_type)::$detail_route)) {
                $target = $remind->target_type::findOrFail($remind->target_id);
                $link = ($remind->target_type)::$detail_route;
                if (Str::startsWith($link, 'sidebar:')) {
                    $this->sidebar = true;
                    $this->link = route(Str::after($link, 'sidebar:'), $target);
                } else {
                    $this->link = route($link, $target);
                }
            }

        }
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.reminder.row');
    }
}
