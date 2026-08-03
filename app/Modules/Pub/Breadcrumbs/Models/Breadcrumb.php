<?php

namespace App\Modules\Pub\Breadcrumbs\Models;


use App\Models\ModuleModel;
use Illuminate\Support\Collection;

class Breadcrumb extends ModuleModel
{
    private $chain;
    public function __construct()
    {
        $this->chain = new Collection();
        $this->add(new BreadcrumbItem('/', __('breadcrumbs.dashboard')));
    }

    public function add(BreadcrumbItem $item) {
        $this->chain->map(fn($item) => $item->setNotLast());
        $this->chain->add($item);
    }

    public function getList() {
        return $this->chain;
    }

    public function getLastName() {
        return collect($this->chain)->last()->getName();
    }

    public function forTitle()
    {
        $title = [];
        $this->chain->each(function($item, $index) use (&$title) {
            if($index > 0)
                $title[] = $item->getName();
        });
        return implode(" / ", $title);
    }
}
