<?php

namespace App\Modules\Pub\Payment\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Organization\Model\Organization;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends ModuleModel
{
    public $timestamps = false;
    protected $fillable = ['date_plan', 'date_fact', 'delay', 'amount_plan', 'amount_fact', 'is_unknown'];
    protected $casts = ['date_plan' => 'datetime', 'date_fact' => 'datetime', 'is_unknown' => 'bool'];

    public function contract_specification()
    {
        return $this->belongsTo(ContractSpecification::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getStatusAttribute()
    {
        if(!empty($this->date_fact)) {
            if(empty($this->date_plan) || ($this->date_plan->greaterThan($this->date_fact) || $this->date_plan->isSameDay($this->date_fact))) {
                $status = PaymentStatus::SUCCESS;
            } else {
                $status = PaymentStatus::EXPIRED;
            }
        } else {
            if($this->date_plan?->isFuture() ?? false) {
                $status =  PaymentStatus::WAITING;
            } else {
                $status =  PaymentStatus::DELAYED;
            }
        }

        return $status->data();
    }

}
