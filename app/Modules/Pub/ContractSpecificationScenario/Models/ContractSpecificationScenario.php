<?php

namespace App\Modules\Pub\ContractSpecificationScenario\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Organization\Model\Organization;
use App\Modules\Pub\Payment\Models\Payment;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractSpecificationScenario extends ModuleModel
{
    use HasLogger;

    public $timestamps = false;
    protected $fillable = ['name', 'sort'];


    public function contract_specification()
    {
        return $this->belongsTo(ContractSpecification::class);
    }

    public function scenario()
    {
        return $this->belongsTo(Scenario::class);
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'contract_specification';
    }

    public static function logLabel(): string
    {
        return 'Сценарий спецификации';
    }

    /** Строки пересоздаются при каждом сохранении — сопоставление по позиции */
    public function logKey(int $index): string
    {
        return $this->logPositionKey($index);
    }

    public function logTitle(?int $index = null): string
    {
        $name = static::logText($this->name, 80);
        if ($name === '') $name = static::logText($this->scenario?->name, 80);

        return 'Сценарий «' . ($name !== '' ? $name : ($index ? '#' . $index : '#' . $this->id)) . '»';
    }

    public static function logIgnore(): array
    {
        return ['sort'];
    }

    public static function logFields(): array
    {
        return [
            'name' => ['label' => 'Название'],
            'scenario_id' => ['label' => 'Сценарий', 'relation' => 'scenario'],
        ];
    }
}
