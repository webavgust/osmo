<?php

namespace App\Modules\Pub\Sector\Repositories;

use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Company\Services\CompanyListFilterService;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\Company\Models\Company;
use Illuminate\Support\Facades\DB;

class SectorRepository
{
    public static function getAll()
    {
        return Sector::all()->keyBy('id');
    }
}
