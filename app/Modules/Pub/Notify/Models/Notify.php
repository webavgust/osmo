<?php

namespace App\Modules\Pub\Notify\Models;

use App\Models\ModuleModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notify extends ModuleModel
{
    use SoftDeletes;

    public static $module_name = 'Уведомление';

    public $fillable = ['link', 'user_id', 'icon', 'title', 'message', 'toastr', 'toastr_showed_at'];

    public function isOwner()
    {
        return $this->user_id == auth()->user()->id;
    }

}
