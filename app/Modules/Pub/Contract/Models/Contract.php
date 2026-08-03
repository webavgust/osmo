<?php

namespace App\Modules\Pub\Contract\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Organization\Model\Organization;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Payment\Models\Payment;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends ModuleModel
{
    public $timestamps = false;
    protected $fillable = ['type', 'uuid', 'cb_signed', 'date', 'number', 'proposal_name'];
    protected $casts = ['cb_signed' => 'bool', 'date' => 'date'];


    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_slug', 'slug');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contract_specifications()
    {
        return $this->hasMany(ContractSpecification::class);
    }

    public function getAmountAttribute()
    {
        return $this->contract_specifications->where('status', '!=', 'canceled')
            ->flatMap->payments
            ->sum('amount_plan');
    }



    public function companyAmountByCurrencies()
    {
        foreach(CompanyRepository::getByID($this->contract_specifications->pluck('company_id')) as $company) {
            $specs = $this->contract_specifications()->where('status', '!=', 'canceled')
                ->whereHas('company', function ($query) use ($company) {
                    $query->where('id', $company->id);
                })->get();

            $ret = [];
            foreach($specs as $spec) {
                if(empty($ret[$spec->currency->slug]))
                    $ret[$spec->currency->slug] = [
                        'amount' => 0,
                        'currency' => $spec->currency,
                    ];
                $ret[$spec->currency->slug]['amount'] += $spec->payments
                    ->sum('amount_plan');
            }
        }

        return $ret;
    }


    public function amountByCurrencies(Company $company)
    {
        $specs = $this->contract_specifications()->where('status', '!=', 'canceled')
            ->whereHas('company', function ($query) use ($company) {
                $query->where('id', $company->id);
            })->get();

        $ret = [];
        foreach($specs as $spec) {
            if(empty($ret[$spec->currency->slug]))
                $ret[$spec->currency->slug] = [
                    'amount' => 0,
                    'currency' => $spec->currency,
                ];
            $ret[$spec->currency->slug]['amount'] += $spec->payments
                ->sum('amount_plan');
        }

        return $ret;
    }

    public function amount(Company $company)
    {
        $specs = $this->contract_specifications()->where('status', '!=', 'canceled')
            ->whereHas('company', function ($query) use ($company) {
                $query->where('id', $company->id);
            })->get();

        return $specs->flatMap->payments
            ->sum('amount_plan');
    }



    public function canDelete()
    {
        return $this->contract_specifications->count() == 0;
    }

    public function getNumberCheckAttribute()
    {
        $ret =  $this->number ?? 'Без номера (' . $this->id . ')';

        if(!empty($this->proposal))
            $ret .= ' - КП: ' . $this->proposal->name;
        return $ret;
    }
}
