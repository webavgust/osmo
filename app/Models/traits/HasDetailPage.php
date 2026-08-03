<?php

namespace App\Models\traits;

trait HasDetailPage
{
    public function getDetailPageLink()
    {
        return route(self::$detail_route, $this);
    }
}
