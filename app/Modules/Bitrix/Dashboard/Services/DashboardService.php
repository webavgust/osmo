<?php

namespace App\Modules\Bitrix\Dashboard\Services;

use App\Modules\Bitrix\Dashboard\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(
        protected $repo = new DashboardRepository(),
    ){}
}
