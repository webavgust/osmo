<?php

namespace App\Providers;

use App\Modules\Pub\Menu\Services\MenuService;
use App\Services\Spider\Spider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.layout', function(\Illuminate\View\View $view) {
            // ТАЙТЛ
            $data = $view->getData();
            $title = $data['title'] ?? (!empty($data['breadcrumbs']) ? $data['breadcrumbs']->forTitle() : 'OSMO AVG');

            // МЕНЮ
            $menu = App(MenuService::class);
            $menu_tree = $menu->getMenuTree();

            // ПАУК
            $spider = App(Spider::class);
            $global = $spider->getStatus(['global' => true]);


            $view->with(compact('menu_tree', 'title', 'global'));
        });
    }
}
