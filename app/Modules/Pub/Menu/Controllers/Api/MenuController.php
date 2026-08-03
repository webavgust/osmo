<?php

namespace App\Modules\Pub\Menu\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Menu\Models\Menu;
use App\Modules\Pub\Menu\Services\MenuService;
use App\Services\AjaxToken\AjaxToken;
use App\Services\Tools\Tools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $service = new MenuService();
        return response()->json($service->jsTree());
    }

    public function update(Request $request)
    {

        $service = new MenuService();
        if($service->updateFromArray(json_decode($request->data, 1))) {
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'error']);
        }
    }

    public function view(Menu $menu) {
        $menu->access = json_decode($menu->accesses()->get()->pluck('code'), 1);
        return $menu;
    }
}
