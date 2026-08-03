<?php

namespace App\Modules\Pub\Company\Models;

use App\Models\ModuleModel;
use App\Modules\Bitrix\CrmCompany\Models\CrmCompany;
use App\Modules\Bitrix\CrmDeal\Models\CrmDealUf;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Country\Models\Country;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\Log\Models\Log;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Project\Models\Project;
use App\Modules\Pub\ProjectConfiguration\Models\ProjectConfiguration;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Sector\Models\Sector;
use Illuminate\Database\Eloquent\Builder;

class Company extends ModuleModel
{
    protected $fillable = ['active', 'name', 'kind'];
    protected $searchable = ["name"];
    protected $casts = ['active' => 'bool'];

    /*** RELATIONS ***/
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function logs()
    {
       return $this->hasMany(Log::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class)->orderBy('id', 'desc');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function license_keys()
    {
        return $this->hasMany(LicenseKey::class);
    }

    public function specifications()
    {
        return $this->hasMany(ContractSpecification::class, 'company_id')->orderBy('name');
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function projects_available()
    {
        return $this->projects()->whereHas('configurations_available');
    }


    public function configurations()
    {
        return $this->hasManyThrough(
            ProjectConfiguration::class,      // Конечная модель
            Project::class,       // Промежуточная модель
            'company_id',              // Внешний ключ в промежуточной таблице (crm_deal_uf)
            'project_id',                // Внешний ключ в конечной таблице (crm_company)
            'id',                   // Локальный ключ (crm_deal)
            'id'     // Ключ в промежуточной таблице для связи с конечной
        );
    }
    public function configurations_available()
    {
        return $this->configurations()->whereDoesntHave('contract_specification');
    }

    /**
     * Scope search для поиска
     *
     * @param Builder $builder
     * @param $search
     * @return Builder
     */
    public function scopeSearch(Builder $builder, $search)
    {
        $words = collect(explode(" ", $search));
        $builder->where(function ($builder) use ($words) {
            $builder->where(function ($builder) use ($words) {
                foreach ($this->searchable as $i => $field) {
                    $builder->orWhere(function ($builder) use ($words, $field) {
                        $words->each(fn($item) => $builder->where($field, 'LIKE', '%' . $item . '%'));
                    });
                }
            });
        });

        return $builder;
    }

    public function getPaymentsAttribute()
    {
        $ret = [
            'future' => $this->contracts->flatMap->contract_specifications->flatMap->payments->whereNull('amount_fact'),
            'past' => $this->contracts->flatMap->contract_specifications->flatMap->payments->whereNotNull('amount_fact')
        ];

        return $ret;
    }

    public function getAmountAttribute()
    {
//        dump($this->specifications);

//        return $this->contract_specifications->where('status', '!=', 'canceled')
//            ->flatMap->payments
//            ->sum('amount_plan');
    }


}
