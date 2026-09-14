<?php

namespace App\Modules\Pub\LicenseKey\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecificationScenario\Models\ContractSpecificationScenario;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Organization\Model\Organization;
use App\Modules\Pub\Payment\Models\Payment;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseKey extends ModuleModel
{
    use HasLogger;

    protected $fillable = ['active', 'key', 'active_from', 'active_to'];
    protected $casts = ['active' => 'bool', 'active_from' => 'date', 'active_to' => 'date'];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function specification()
    {
        return $this->belongsTo(ContractSpecification::class, 'contract_specification_id');
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'specification';
    }

    public static function logLabel(): string
    {
        return 'Лицензионный ключ';
    }

    public function logTitle(?int $index = null): string
    {
        $key = trim((string) $this->key);

        return 'Ключ ' . ($key !== '' ? $key : '#' . $this->id);
    }

    public static function logFields(): array
    {
        return [
            'active' => ['label' => 'Активный', 'type' => 'bool'],
            'key' => ['label' => 'Ключ'],
            'active_from' => ['label' => 'Дата действия, с', 'type' => 'date'],
            'active_to' => ['label' => 'Дата действия, по', 'type' => 'date'],
            'company_id' => ['label' => 'Компания', 'relation' => 'company'],
            'count' => ['label' => 'Кол-во'],
            'comment' => ['label' => 'Комментарий'],
        ];
    }
}
