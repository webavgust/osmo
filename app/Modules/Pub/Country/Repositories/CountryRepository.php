<?php

namespace App\Modules\Pub\Country\Repositories;

use App\Modules\Pub\Country\Models\Country;

class CountryRepository
{
    public static function getAll()
    {
        return Country::orderBy('name')->get();
    }
}
