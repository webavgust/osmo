<?php


namespace App\ViewComposers;


use App\Modules\Pub\Menu\Models\Menu;
use App\Modules\Pub\Menu\Services\MenuService;
use Illuminate\View\View;

class NavigationComposer
{
    public function compose(View $view)
    {
        $menu = App(MenuService::class);
        $menu_tree = $menu->buildTree();
        return $view->with(compact('menu_tree'));
    }

}
