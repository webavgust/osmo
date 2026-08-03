<?php

namespace App\Modules\Pub\Constant\Models;

use App\Models\ModuleModel;
use Carbon\Carbon;

class Constant extends ModuleModel
{
    public $timestamps = false;
    protected $table = 'consts';

    /**
     * сеттер для константы
     *
     * @param $key ключ
     * @param $value значение
     * @return void
     */
    public static function set($key, $value)
    {
        $count = \DB::selectOne('SELECT COUNT(*) as value FROM consts WHERE `key` = ?', [$key]);
        if ($count->value == 1) {
            \DB::update('UPDATE consts SET value = ? WHERE `key` = ?', [$value, $key]);
        } else {
            \DB::update('INSERT INTO consts SET value = ?, `key` = ?', [$value, $key]);
        }
    }

    /**
     * геттер для константы
     *
     * @param string $key
     * @return string|integer|null
     */
    public static function get(string $key)
    {
        $ret = \DB::selectOne('SELECT value FROM consts WHERE `key` = ? ', [$key]);
        if (empty($ret->value))
            return null;

        return $ret->value;
    }

}
