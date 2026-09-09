<?php

namespace App\Modules\Pub\Contract\Model;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Organization\Model\Organization;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends ModuleModel
{
    public $timestamps = false;
    protected $fillable = ['type', 'amount'];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function proposals()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_slug', 'slug');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'slug');
    }

}
