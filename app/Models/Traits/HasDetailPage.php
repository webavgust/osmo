<?php

// patch v29: каталог переименован в Traits (на Linux регистр важен, namespace был App\Models\traits)
namespace App\Models\Traits;

trait HasDetailPage
{
    public function getDetailPageLink()
    {
        return route(self::$detail_route, $this);
    }
}
