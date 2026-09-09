<?php

namespace App\Providers;

use App\Facades\Tools;
use App\View\Components\Reminder\Row;
use http\Client\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Все связывания контейнера, которые должны быть зарегистрированы.
     *
     * @var array
     */
    public $bindings = [
    ];


    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        App::bind('tools', function()
        {
            return new Tools();
        });
    }
}
