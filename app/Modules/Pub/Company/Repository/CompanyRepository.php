<?php

namespace App\Modules\Pub\Company\Repository;

use App\Modules\Pub\Country\Models\Country;

class CompanyRepository
{
    public static function getAll()
    {
        return Country::orderBy('name')->get();
    }
}
