<?php

namespace App\Providers;

use App\Facades\Tools;
use App\Modules\Pub\OrderTask\FileGenerators\Interfaces\OrderTaskFileGeneratorInterface;
use App\Modules\Pub\OrderTask\FileGenerators\OrderTaskFileGeneratorDOCX;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\OrderTask\Observers\OrderTaskObserver;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\UserGroup\Repositories\UserDepartmentPortalRepository;
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
        OrderTaskFileGeneratorInterface::class => OrderTaskFileGeneratorDOCX::class,
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

        // Observers
        OrderTask::observe(OrderTaskObserver::class);

        App::bind('tools', function()
        {
            return new Tools();
        });

        $this->app->bind(OrderTaskFileGeneratorInterface::class, OrderTaskFileGeneratorDOCX::class);


    }
}
