<?php

namespace App\Modules\Pub\Menu\Models;


use App\Models\ModuleModel;
use App\Modules\Pub\Access\Models\Access;
use Illuminate\Support\Str;

class Menu extends ModuleModel
{

    public static $module_name = 'Меню';

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    protected $fillable = [
        'active', 'parent_id', 'name', 'icon', 'url'
    ];




    public function accesses() {
        return $this->belongsToMany(Access::class);
    }


    public static function boot()
    {
        parent::boot();
        static::retrieved(function($model)
        {

            if(Str::startsWith($model->url, 'route:')) {
                $model->url = route(Str::afterLast($model->url, 'route:'));
            }
        });
    }


    public function getUrl()
    {
//        if(Str::startsWith($this->url, 'route:'))
//            return route(Str::afterLast($this->url, 'route:'));

        return $this->url;
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }


    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }



    public function scopeIsLive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOfSort($query, $sort)
    {
        foreach ($sort as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }
}
