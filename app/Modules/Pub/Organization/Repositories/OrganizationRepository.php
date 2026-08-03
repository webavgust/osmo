<?php

namespace App\Modules\Pub\Organization\Repositories;

use App\Modules\Pub\Organization\Model\Organization;

class OrganizationRepository
{
    public static function getOnce(mixed $value)
    {
        return Organization::where('id', $value)->orWhere('slug', $value)->first();
    }

    public static function getAll()
    {
        return Organization::all();
    }
}
