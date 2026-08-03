<?php

namespace App\Modules\Pub\LicenseKey\Models;

use App\Models\ModuleModel;
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
}
