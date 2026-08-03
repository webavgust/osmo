<?php

namespace App\Modules\Pub\Access\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Menu\Models\Menu;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Database\Eloquent\Builder;

class Access extends ModuleModel
{

    public static $module_name = 'Доступ';
    protected $fillable = ['name', 'code', 'description', 'sort', 'class', 'method'];

    public const ACCESS_EVALUATION_FINAL_CHECK = 48;

    /**
     * Добавление сортировки по умолчанию
     *
     * @return void
     */
    static public function boot()
    {
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('sort', 'asc');
        });
    }


    /*** RELATIONS ***/

    public function access_group()
    {
        return $this->belongsTo(AccessGroup::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class);
    }

    public function user_groups()
    {
        return $this->belongsToMany(UserGroup::class)->withPivot('mode');
    }

    public function user_departments()
    {
        return $this->belongsToMany(UserDepartment::class)->withPivot('mode');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('mode');
    }
}
