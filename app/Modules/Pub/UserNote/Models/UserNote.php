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

    protected $casts = [
        'done_at' => 'datetime',
    ];

    /**
     * Задача выполнена (отметка в виджете «Блокнот» или в сайдбаре правки)
     *
     * @return bool
     */
    public function isDone(): bool
    {
        return $this->done_at !== null;
    }

    public function canEdit()
    {
        return is_admin() || $this->user_id == auth()->id();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
