<?php

namespace App\Modules\Pub\Breadcrumbs\Traits;

use App\Modules\Pub\Breadcrumbs\Models\Breadcrumb;
use App\Modules\Pub\Breadcrumbs\Models\BreadcrumbItem;

trait HasBreadcrumb
{
    protected $breadcrumb;

    public function breadcrumb_add(string|null $link, string $item, bool $muted = false)
    {
        if(empty($this->breadcrumb))
            $this->breadcrumb = App(Breadcrumb::class);

        $this->breadcrumb->add(new BreadcrumbItem($link, $item, $muted));
    }
}
