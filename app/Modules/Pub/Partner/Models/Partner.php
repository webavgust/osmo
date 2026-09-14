<?php

namespace App\Modules\Pub\Partner\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use Illuminate\Database\Eloquent\Builder;

class Partner extends ModuleModel
{
    use HasLogger;

    protected $fillable = ['active', 'name', 'region', 'type', 'grade', 'contact', 'phone'];
    protected $searchable = ["name", "region"];
    protected $casts = ['active' => 'bool'];

    /*** RELATIONS ***/
    public function companies()
    {
        return $this->hasMany(Company::class)->orderBy('name');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class)->orderBy('type');
    }

    /**
     * Сопоставление с компаниями Битрикс24 (patch v23)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function crm_companies()
    {
        return $this->hasMany(PartnerCrmCompany::class, 'partner_id')->orderBy('crm_company_id');
    }

    /**
     * Id компаний Битрикса, сопоставленных партнёру
     *
     * Ими ограничиваются выборки сделок: crm_deal.company_id IN (...).
     * Пустой массив — партнёр ещё не сопоставлен.
     *
     * @return array
     */
    public function crmCompanyIds(): array
    {
        return $this->relationLoaded('crm_companies')
            ? $this->crm_companies->pluck('crm_company_id')->all()
            : $this->crm_companies()->pluck('crm_company_id')->all();
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

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logLabel(): string
    {
        return 'Партнёр';
    }

    public function logUrl(): ?string
    {
        return route('partner.detail', $this);
    }

    public static function logChildren(): array
    {
        return [
            'contracts' => Contract::class,
            'crm_companies' => PartnerCrmCompany::class,
        ];
    }

    public static function logFields(): array
    {
        return [
            'active' => ['label' => 'Активность', 'type' => 'bool'],
            'name' => ['label' => 'Название'],
            'type' => ['label' => 'Тип партнёра', 'enum' => PartnerType::class],
            'grade' => ['label' => 'Уровень партнёрства', 'enum' => PartnerGrade::class],
            'region' => ['label' => 'Регион'],
            'contact' => ['label' => 'Контактное лицо'],
            'phone' => ['label' => 'Телефон'],
        ];
    }
}
