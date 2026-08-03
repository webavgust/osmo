<?php

namespace App\Modules\Pub\Partner\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use Illuminate\Database\Eloquent\Builder;

class Partner extends ModuleModel
{
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

}
