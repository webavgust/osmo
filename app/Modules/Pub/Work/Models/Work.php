<?php

namespace App\Modules\Pub\Work\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use Illuminate\Database\Eloquent\Builder;

class Work extends ModuleModel
{
    protected $fillable = ['name', 'extended', 'notice', 'count', 'type', 'cost', 'group', 'lang'];
    protected $searchable = ['name', 'extended', 'notice'];
    public $selectable = ['name', 'extended', 'notice', 'count', 'type'];

    /*** RELATIONS ***/



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

    public function getDescriptionAttribute()
    {
        return $this->name . $this->extended;
    }

}
