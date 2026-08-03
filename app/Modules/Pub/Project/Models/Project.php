<?php

namespace App\Modules\Pub\Project\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\ProjectConfiguration\Models\ProjectConfiguration;

class Project extends ModuleModel
{
    protected $fillable = ['prefix', 'name'];

    # RELATIONS
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function configurations()
    {
        return $this->hasMany(ProjectConfiguration::class);
    }

    public function configurations_available()
    {
        return $this->configurations()->whereDoesntHave('contract_specification');
    }



    # CAN
    public function canDelete(): bool
    {
        return $this->configurations->isEmpty();
    }

    # ATTRIBUTES
}
