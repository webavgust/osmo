<?php

namespace App\Modules\Pub\ContractSpecification\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\ContractSpecification\Services\SpecProposalService;
use App\Modules\Pub\ContractSpecification\Services\SpecReconcileService;
use App\Modules\Pub\ContractSpecificationScenario\Models\ContractSpecificationScenario;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\Organization\Model\Organization;
use App\Modules\Pub\Payment\Models\Payment;
use App\Modules\Pub\ProjectConfiguration\Models\ProjectConfiguration;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractSpecification extends ModuleModel
{
    use HasLogger;

    public $timestamps = false;
    protected $fillable = ['name', 'date_create', 'amount', 'status', 'is_signed', 'report_data', 'currency'];
    protected $casts = ['is_signed' => 'boolean', 'report_data' => 'json', 'date_create' => 'date'];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }


    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function contract_specification_scenarios()
    {
        return $this->hasMany(ContractSpecificationScenario::class);
    }

    public function license_keys()
    {
        return $this->hasMany(LicenseKey::class);
    }

    public function project_configurations()
    {
        return $this->hasMany(ProjectConfiguration::class);
    }

    /**
     * Привязки КП к спецификации (patch v16)
     */
    public function proposal_links()
    {
        return $this->hasMany(ContractSpecificationProposal::class, 'contract_specification_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_slug', 'slug');
    }

    public function canDelete()
    {
        // patch v28: раньше is_admin(), но «админом» был каждый (право super_user у всех).
        // Признак админа стал доступом в админ-панель, а удаление спецификаций владелец
        // оставил всем пользователям портала
        return auth()->check();
    }

    public function getAmountPastAttribute()
    {
        return $this->payments()->whereNotNull('date_fact')->sum('amount_fact');
    }
    public function getAmountFutureAttribute()
    {
        return $this->payments()->whereNull('date_fact')->sum('amount_plan');
    }
    public function getAmountAllAttribute()
    {
        return $this->payments()->sum('amount_plan');
    }

    public function getNameFullAttribute()
    {
        $contract = $this->contract;
        $type = ContractType::from($contract->type);

        return$type->data()['label'] . ' (' . $contract->number . ')  ->  ' . $this->name;
    }

    /**
     * Прикреплённые КП — последние редакции
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAttachedProposalsAttribute()
    {
        return SpecProposalService::attached($this);
    }

    /**
     * Сверка суммы спецификации с платежами и КП
     *
     * @return array
     */
    public function getReconcileAttribute()
    {
        return SpecReconcileService::check($this);
    }

    /**
     * Дата спецификации, а если своей нет — дата рамочного договора
     *
     * @return \Illuminate\Support\Carbon|null
     */
    public function getDateAttribute()
    {
        return $this->date_create ?? $this->contract?->date;
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'contract';
    }

    public static function logLabel(): string
    {
        return 'Спецификация';
    }

    public function logTitle(?int $index = null): string
    {
        $name = static::logText($this->name, 80);

        return 'Спецификация «' . ($name !== '' ? $name : '#' . $this->id) . '»';
    }

    public static function logChildren(): array
    {
        return [
            'payments' => Payment::class,
            'contract_specification_scenarios' => ContractSpecificationScenario::class,
            'proposal_links' => ContractSpecificationProposal::class,
            'license_keys' => LicenseKey::class,
        ];
    }

    public static function logIgnore(): array
    {
        return ['report_data', 'project_configuration_id'];
    }

    public static function logFields(): array
    {
        return [
            'name' => ['label' => 'Название'],
            'status' => ['label' => 'Статус', 'enum' => ContractSpecificationStatus::class],
            'company_id' => ['label' => 'Компания', 'relation' => 'company'],
            'currency_slug' => ['label' => 'Валюта', 'relation' => 'currency', 'title' => 'name'],
            'date_create' => ['label' => 'Дата', 'type' => 'date'],
            'is_signed' => ['label' => 'Подписана', 'type' => 'bool'],
            'amount' => ['label' => 'Сумма', 'type' => 'money'],
            'closed_at' => ['label' => 'Дата закрытия', 'type' => 'date'],
        ];
    }
}
