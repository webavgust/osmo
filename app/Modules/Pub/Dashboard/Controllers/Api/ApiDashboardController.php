<?php

namespace App\Modules\Pub\Dashboard\Controllers\Api;

use App\Modules\Pub\Dashboard\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ApiDashboardController
{
    private $service;

    public function __construct()
    {
        $this->service = new DashboardService();
    }

    public function set_user_mode(Request $request)
    {
        $request->validate([
            'mode' => 'required|string',
            'users' => 'array|required_if:mode,select'
        ]);

        $params = [];
        if($request->mode == 'select')
        {
            $params['users'] =  collect($request->users)->unique()->toArray();
        }


        $this->service->setMode($request->mode, $params );
        return Response::json(['status' => 'success']);
    }

    public function set_sub_user_mode(Request $request)
    {
        $request->validate([
            'user' => 'required|int'
        ]);

        $params = [];
        $this->service->setSubUser($request->user);
        return Response::json(['status' => 'success']);
    }
}
