<?php

namespace App\Traits\Eloquent\Model;

use App\Models\ModuleModel;
use App\Modules\Pub\LabMeasure\Models\LabMeasure;
use App\Services\Tools\Tools;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait Searchable
{

    public function scopeSearch(Builder $builder, string $query): void
    {
        $query_opposite = Tools::strOpposite($query);

        $builder
            ->where('id', 0)
            ->orWhere(function(Builder $builder) use ($query, $query_opposite) {
            foreach($this->searchable as $field => $from) {
                if(Str::length($query) < $from) continue;

                $builder->orWhere($field, 'LIKE', "%" . $query . "%");
                $builder->orWhere($field, 'LIKE', "%" . $query_opposite . "%");
            }
        });
    }
}
