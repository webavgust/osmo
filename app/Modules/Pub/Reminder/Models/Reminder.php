<?php

namespace App\Modules\Pub\Reminder\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Reminder\Traits\HasReminder;
use App\Modules\Pub\ReminderTime\Models\ReminderTime;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends ModuleModel
{
    use SoftDeletes, HasReminder;

    protected $fillable = ['remind_at', 'title', 'message', 'group', 'hide'];
    public static $module_name = 'Напоминание';
    public static $module_icon = 'fa-bell';
    protected $casts = [
        'remind_at' => 'datetime',
        'hide' => 'boolean'
    ];

    protected static function booted()
    {
        static::deleting(function ($item) {
            // удаляем из БД
            if($item->isForceDeleting()) {
                $item->reminder_times()->get()->each->delete();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function target()
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }

    public function reminder_times()
    {
        return $this->hasMany(ReminderTime::class);
    }



    public function hasFutureJobs()
    {
        return $this->reminder_times()->where('notified', 0)->count() > 0;
    }

    public function scopeGroupUsers()
    {
        return Reminder::select(['group', 'user_id'])->where('group', $this->group)->withTrashed()->pluck('user_id');
    }

    public function scopeHasAccess($query, $user)
    {
        return $query->where(function($query) use ($user) {
            $query->where('user_id', $user->id)
            ->orWhere('created_by', $user->id);
        });
    }


    public function canDelete()
    {
        return ($this->created_by == auth()->id() || auth()->user()->isAdmin()) && !$this->trashed() && $this->reminder_times()->where('notified', 1)->count() == 0;
    }

    public function canEdit()
    {
        return ($this->created_by == auth()->id() || auth()->user()->isAdmin()) && !$this->trashed();;
    }

    public function canFullEdit()
    {
        return $this->canEdit() && $this->reminder_times()->where('notified', 1)->count() == 0;
    }
}
