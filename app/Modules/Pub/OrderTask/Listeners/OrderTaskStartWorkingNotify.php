<?php

namespace App\Modules\Pub\OrderTask\Listeners;

use App\Modules\Pub\OrderTask\Controllers\OrderTaskController;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\OrderTask\Services\OrderTaskService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Services\Notificator\Notificator;
use App\Services\Portal\Events;
use Illuminate\Support\Facades\Log;

class OrderTaskStartWorkingNotify
{
    public function handle($event)
    {
        $arUsers = collect();

        // Начальник лаборатории
        $arUsers = $arUsers->merge(UserRepository::getLabSupervisors());

        // Кураторы по направлению, в зависимости от содержания работ
        $arCurators = UserRepository::getCuratorsByDirection();

        // Исполнители по наравлению
        $arExecutors = UserRepository::getExecutorsByDirection();


        if($event->order_task->objectsA->isNotEmpty()) {
            $arUsers = $arUsers->merge(UserRepository::getDirectionA($arCurators));
            $arUsers = $arUsers->merge(UserRepository::getDirectionA($arExecutors));
        }
        if($event->order_task->objectsB->isNotEmpty()) {
            $arUsers = $arUsers->merge(UserRepository::getDirectionB($arCurators));
            $arUsers = $arUsers->merge(UserRepository::getDirectionB($arExecutors));
        }

        $arUsers = collect([auth()->user()]);

        $arUsers->unique()->each(fn($user) => Notificator::send($user, [
            'template' => 'order_task.start_working',
            'template_data' => ['order_task' => $event->order_task],
            'link' => route('order_task.detail', $event->order_task),
            'toastr' => 1,
            'mail' => \App\Mail\EventMail::class,
            ], ['site'])
        );
    }
}
