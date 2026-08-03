<?php

namespace App\Modules\Pub\Menu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Menu\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class MenuController extends Controller
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(route('menu.index'), 'Меню');
    }

    public function index()
    {
        $groups = AccessGroup::where('id', '>', 0)->get();
        return View::make('pub::menu.index', [
            'breadcrumbs' => $this->breadcrumb,
            'groups' => $groups
        ]);
    }

    public function update(Request $request, Menu $menu)
    {

        $menu->fill($request->all())->save();
        $accesses = !empty($request->access) ? Access::whereIn('code', $request->access)->get() : [];
        $menu->accesses()->sync($accesses);
        return redirect()->route('menu.index');
    }
}
