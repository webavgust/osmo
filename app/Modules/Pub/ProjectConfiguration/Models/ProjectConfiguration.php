<?php

namespace App\Modules\Pub\ProjectConfiguration\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Project\Models\Project;

class ProjectConfiguration extends ModuleModel
{
    protected $fillable = ['number', 'platform', 'duration', 'streams', 'sort', 'comment'];

    # RELATIONS

    # ATTRIBUTES
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function contract_specification()
    {
        return $this->belongsTo(ContractSpecification::class);
    }
}
