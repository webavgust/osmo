<?php

namespace App\Services\Notificator\Interfaces;

use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;

interface NotificationInterface
{
    public function send(User $user);
}
