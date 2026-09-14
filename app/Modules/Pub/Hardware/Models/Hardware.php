<?php

namespace App\Modules\Pub\Hardware\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;

class Hardware extends ModuleModel
{
    use HasLogger;

    protected $fillable = ['name', 'count', 'params', 'sort'];

    public function proposal_variant()
    {
        return $this->belongsTo(ProposalVariant::class);
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'proposal_variant';
    }

    public static function logLabel(): string
    {
        return 'Вычислительные ресурсы';
    }

    public function logTitle(?int $index = null): string
    {
        $name = static::logText($this->name, 60);

        return 'Оборудование ' . ($name !== '' ? '«' . $name . '»' : '#' . $this->id);
    }

    public static function logIgnore(): array
    {
        return ['sort'];
    }

    public static function logFields(): array
    {
        return [
            'name' => ['label' => 'Наименование', 'type' => 'html'],
            'count' => ['label' => 'Количество', 'type' => 'html'],
            'params' => ['label' => 'Параметры', 'type' => 'html'],
        ];
    }
}
