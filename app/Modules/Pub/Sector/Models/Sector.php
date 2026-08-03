<?php

namespace App\Modules\Pub\Sector\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends ModuleModel
{
    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
