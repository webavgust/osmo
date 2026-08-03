<?php

namespace App\Modules\Pub\UserNote\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Reminder\Traits\HasReminder;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNote extends ModuleModel
{
    use HasReminder;

    public static $module_name = 'Личная заметка';
    public static $module_icon = 'fa-note';
    public static $detail_route = 'dashboard.index';

    protected $fillable = ['title', 'text', 'favorite'];

    public function canEdit()
    {
        return is_admin() || $this->user_id == auth()->id();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
