<?php

namespace App\Providers;

use App\Services\Tools\Tools;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class FacadeServiceProvider extends ServiceProvider
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
        $this->app->bind('tools', fn () => new Tools());
    }
}
