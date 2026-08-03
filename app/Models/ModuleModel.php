<?php

namespace App\Models;

class ModuleModel extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'prefix',
    ];
    public static $module_name = 'Module name';
    public static $detail_route = '{module_name}.detail';

    /**
     * Получить имя модуля
     *
     * @return string
     */
    public static function getModuleName()
    {
        return static::$module_name;
    }

    /**
     * Получить название класса
     *
     * @param mixed $module
     * @return false|string
     */
    public static function getClassName(mixed $module)
    {
        if(!in_array($module, config('modular.modules.Pub'))) return false;
        $class_name = config('modular.base_namespace') . '\\Pub\\' . $module . '\\Models\\' . $module;
        return $class_name;
    }

    /**
     * Получить код модуля
     *
     * @return string
     */
    public function getModuleSlug()
    {
        return _module_name($this::class);
    }

    public function getRoute()
    {
        return $this::$detail_route ? route($this::$detail_route, $this) : null;
    }

}

