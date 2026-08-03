<?php

namespace App\Modules\Pub\ContractSpecificationScenario\Models;

use App\Models\ModuleModel;
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
}
