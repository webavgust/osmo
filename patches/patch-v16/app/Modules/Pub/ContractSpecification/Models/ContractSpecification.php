<?php

namespace App\Modules\Pub\ContractSpecification\Models;

use App\Models\ModuleModel;
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
    public $timestamps = false;
    protected $fillable = ['name', 'amount', 'status', 'is_signed', 'report_data', 'currency'];
    protected $casts = ['is_signed' => 'boolean', 'report_data' => 'json'];


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
        return is_admin();
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
}
