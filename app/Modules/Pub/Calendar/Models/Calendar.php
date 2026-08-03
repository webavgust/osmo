<?php

namespace App\Modules\Pub\Calendar\Models;

use App\Models\ModuleModel;
use App\Models\traits\HasDetailPage;
use App\Modules\Pub\Reminder\Traits\HasReminder;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class Calendar extends ModuleModel
{
    use SoftDeletes, HasReminder, HasDetailPage;

    public static $module_name = 'Событие';
    public static $module_icon = 'fa-calendar';
    public static $detail_route = 'sidebar:calendar.sidebar_show';

    public $table = 'calendar';
    protected $fillable = ['mode', 'all_day', 'start', 'end', 'title', 'text', 'color', 'duration', 'target_sub', 'title_icon'];
    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'created_at' => 'datetime',
    ];

    /*** RELATIONS ***/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function target()
    {
        return $this->morphTo();
    }

    public function canEdit()
    {
        return ($this->user->id === auth()->id() && $this->editable) || auth()->user()->isAdmin();
    }
}
