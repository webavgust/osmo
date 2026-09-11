<?php

namespace App\Modules\Pub\DealProject\Models;

use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use Illuminate\Database\Eloquent\Model;

/**
 * Спецификация, отнесённая к проекту (patch v24).
 *
 * from_proposal = true — спецификация пришла из КП сделок проекта; такие
 * в попапе отмечены и не снимаются.
 */
class DealProjectSpecification extends Model
{
    protected $table = 'deal_project_specifications';
    public $timestamps = false;

    protected $fillable = ['deal_project_id', 'contract_specification_id', 'from_proposal'];
    protected $casts = ['from_proposal' => 'boolean'];

    public function deal_project()
    {
        return $this->belongsTo(DealProject::class, 'deal_project_id');
    }

    public function specification()
    {
        return $this->belongsTo(ContractSpecification::class, 'contract_specification_id');
    }
}
