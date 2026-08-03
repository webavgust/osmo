<?php

namespace App\Providers;

use App\Events\OrderTask\Agreement\OrderTaskAgreementDeclined;
use App\Listeners\OrderTask\Agreement\OrderTaskAgreementDecisionDeclinedResponsibleNotification;
use App\Modules\Pub\Evaluation\Events\EvaluationChangeStatus;
use App\Modules\Pub\Evaluation\Events\EvaluationUpdateEvent;
use App\Modules\Pub\Evaluation\Listeners\EvaluationPortalUpdateStatus;
use App\Modules\Pub\Evaluation\Listeners\EvaluationUpdatePortalHook;
use App\Modules\Pub\OrderTask\Events\OrderTaskChangeStatus;
use App\Modules\Pub\OrderTask\Events\OrderTaskStartWorking;
use App\Modules\Pub\OrderTask\Events\OrderTaskUpdateEvent;
use App\Modules\Pub\OrderTask\Listeners\OrderTaskChangeStatusPortalAvgHook;
use App\Modules\Pub\OrderTask\Listeners\OrderTaskChangeStatusPortalHook;
use App\Modules\Pub\OrderTask\Listeners\OrderTaskRecreatePortalHook;
use App\Modules\Pub\OrderTask\Listeners\OrderTaskStartWorkingNotify;
use App\Modules\Pub\OrderTask\Listeners\OrderTaskUpdateListener;
use App\Modules\Pub\OrderTask\Listeners\OrderTaskUpdatePortalHook;
use App\Modules\Pub\OrderTask\Listeners\VisitCheckStatus;
use App\Modules\Pub\Visit\Events\VisitChangeStatus;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        OrderTaskUpdateEvent::class => [
            OrderTaskUpdateListener::class,
            OrderTaskUpdatePortalHook::class,
            OrderTaskRecreatePortalHook::class,
        ],

        OrderTaskChangeStatus::class => [
            OrderTaskChangeStatusPortalHook::class,
            OrderTaskChangeStatusPortalAvgHook::class,
        ],

        OrderTaskStartWorking::class => [
            OrderTaskStartWorkingNotify::class
        ],

        EvaluationChangeStatus::class => [
            EvaluationPortalUpdateStatus::class
        ],

        // при смене статуса акта проверяем надо ли закрыть ТЗ
        VisitChangeStatus::class => [
            VisitCheckStatus::class
        ],

        EvaluationUpdateEvent::class => [
            EvaluationUpdatePortalHook::class,
            EvaluationPortalUpdateStatus::class
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
