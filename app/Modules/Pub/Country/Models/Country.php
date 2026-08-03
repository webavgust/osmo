<?php

namespace App\Modules\Pub\Country\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends ModuleModel
{
    public $timestamps = false;
    protected $fillable = ['code', 'name'];

    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
