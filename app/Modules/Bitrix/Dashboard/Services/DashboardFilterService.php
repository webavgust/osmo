<?php

namespace App\Modules\Bitrix\Dashboard\Services;

use Illuminate\Support\Facades\Cache;

class DashboardFilterService
{
    public static function setFilter(array $data = null)
    {
        Cache::set('dashboard_filter', $data);
    }

    public static function getFilter()
    {
        return Cache::get('dashboard_filter');
    }
}
