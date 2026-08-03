<?php

namespace App\Providers;

use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\Access\Policies\AccessGroupPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class AuthServiceProvider extends ServiceProvider
{


    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        if(empty($_SERVER['USERNAME']) || $_SERVER['USERNAME'] != 'avg.den') {
            if (!Schema::hasTable('accesses')) return false;
            Access::all()->each(fn($item) => Gate::define($item->code, [$item->class, $item->method]));
        }



        // todo переделать авторизацию по токены для ajax
        //        Auth::viaRequest('custom-token', function (Request $request) {
        //            return User::where('token', $request->token)->first() ;
        //        });
    }
}
