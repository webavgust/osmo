<?php

namespace App\Modules\Pub\UserSettings\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\User\Models\User;

class UserSetting extends ModuleModel
{

    public static $module_name = 'Настройки пользователя';

    protected $fillable = ['user_id'];
    protected $casts = [
        'settings' => 'array',
    ];

    public function __construct()
    {
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function set($field, $value = null)
    {
        $settings = $this->settings;
        if(is_array($field))
        {
            foreach($field as $field => $value) {
                $settings[$field] = $value;
            }
        } else {
            $settings[$field] = $value;
        }
        $this->settings = $settings;
        $this->save();
    }

    public function read($field)
    {
        $settings = $this->settings;
        return $settings[$field] ?? null;
    }
}
