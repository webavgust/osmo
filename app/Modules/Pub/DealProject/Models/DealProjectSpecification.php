<?php

namespace App\Modules\Pub\DealProject\Models;

use App\Models\Traits\HasLogger;
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
    use HasLogger;

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
    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v32) ***/

    public static function logParentRelation(): ?string
    {
        return 'deal_project';
    }

    public static function logLabel(): string
    {
        return 'Спецификация проекта';
    }

    /** Спецификация в проекте одна — сопоставляем по id спецификации */
    public function logKey(int $index): string
    {
        return (string) $this->contract_specification_id;
    }

    public function logTitle(?int $index = null): string
    {
        $spec = $this->specification;

        return $spec ? $spec->logTitle() : 'Спецификация #' . $this->contract_specification_id;
    }

    public static function logFields(): array
    {
        return [
            'contract_specification_id' => ['label' => 'Спецификация', 'relation' => 'specification'],
            'from_proposal' => ['label' => 'Из КП сделки', 'type' => 'bool'],
        ];
    }
}
