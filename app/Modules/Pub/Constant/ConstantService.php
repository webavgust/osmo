<?php

namespace App\Modules\Pub\Constant;

use App\Modules\Pub\Constant\Models\Constant;
use Carbon\Carbon;

class ConstantService
{
    public static function setBitrixSyncTime(string $table, \Illuminate\Support\Carbon $time)
    {
        $json = Constant::get('bitrix_update_timestamps');
        $values = !empty($json) ? json_decode($json, 1) : [];
        $values[$table] = $time;

        Constant::set('bitrix_update_timestamps', json_encode($values));
    }

    public static function getBitrixSyncTime(string $table = null)
    {
        $json = json_decode(Constant::get('bitrix_update_timestamps'), 1);
        if(!empty($table) && !empty($json[$table])) {
            return new Carbon($json[$table]);
        } elseif(empty($table)) {
            return $json;
        } else {
            return null;
        }
    }
}
