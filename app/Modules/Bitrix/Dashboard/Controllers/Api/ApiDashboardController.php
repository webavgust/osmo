<?php

namespace App\Modules\Bitrix\Dashboard\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\Dashboard\Services\DashboardFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class ApiDashboardController extends Controller
{
    public function set_currency(Request $request)
    {
        $request->validate([
            'currency' => 'required|exists:currencies,slug',
        ]);


        Cache::set('dashboard_currency', $request->input('currency'));

        return ['result' => 'success'];
    }

    public function set_filter(Request $request)
    {
        DashboardFilterService::setFilter($request->input('filter'));

        return ['result' => 'success'];
    }

    public function remove_filter(Request $request)
    {
        DashboardFilterService::setFilter();

        return ['result' => 'success'];
    }
}
