<?php

namespace App\Modules\Pub\User\Models;


use App\Casts\JsonCast;
use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\Access\Services\AccessUserService;
use App\Modules\Pub\Calendar\Models\Calendar;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Course\Models\Work;
use App\Modules\Pub\EducationApplicationAgreement\Models\EvaluationDiscountAgreement;
use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\OrderTaskAgreement\Models\OrderTaskAgreement;
use App\Modules\Pub\Payment\Models\Payment;
use App\Modules\Pub\PlanVisit\Models\PlanVisit;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Reminder\Models\Reminder;
use App\Modules\Pub\Salary\Models\Salary;
use App\Modules\Pub\Sampler\Models\Sampler;
use App\Modules\Pub\Teacher\Models\Teacher;
use App\Modules\Pub\UserNote\Models\UserNote;
use App\Modules\Pub\UserSettings\Models\UserSetting;
use App\Modules\Pub\UserWorkCalendar\Models\UserWorkCalendar;
use App\Modules\Pub\VisitMeasureWork\Models\VisitMeasureWork;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    // SoftDeletes (patch v28): из админ-панели пользователь удаляется только мягко
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public $access_service;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['id', 'name', 'email', 'login', 'active', 'last_name', 'second_name', 'personal_gender', 'personal_photo', 'personal_mobile', 'work_department', 'work_position', 'work_phone', 'personal_birthday', 'telegram_id', 'full_name', 'last_hit_at', 'is_sync'];
    static $showFields = ['id', 'name', 'email', 'login', 'active', 'last_name', 'second_name', 'personal_gender', 'personal_photo', 'personal_mobile', 'work_department', 'work_position', 'work_phone', 'personal_birthday', 'full_name', 'initials'];
    static protected $portalFillable = [
        "order_name", "order_sent_to_techdep", "customer_id", "customer_name", "contract_id", "contract_conclusion", "author_id", "manager_id", "curator_id", "last_control_date", "second_control_date", "md_specify_days", "md_specify_finaldate", "md_specify_periodicity,", "md_specify_locationplace", "full_name"
    ];
    protected $searchable = [
        "name", "email", "last_name", "second_name", "personal_mobile", "work_phone", "personal_birthday"
    ];

    /**
     * Скрытые свойства
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Свойства для преобразования
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_hit_at' => 'datetime',
        'personal_photo' => JsonCast::class,
        'is_sync' => 'bool',
        'is_admin' => 'bool',
        'ui_theme_switch' => 'bool',
        'log_view' => 'bool',
    ];


    /*** RELATIONS ***/

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'manager_id');
    }

    public function setting()
    {
        return $this->hasOne(UserSetting::class);
    }




    public function calendar_events()
    {
        return $this->hasMany(Calendar::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class)->orderBy('id', 'desc');
    }

    public function notes()
    {
        return $this->hasMany(UserNote::class)->orderBy('favorite', 'desc')->orderBy('created_at', 'desc');
    }

    public function parent_users()
    {
        return $this->belongsToMany(User::class, 'user_sub_users', 'sub_user_id')->orderBy('full_name', 'asc');
    }

    public function sub_users()
    {
        return $this->belongsToMany(User::class, 'user_sub_users', 'user_id', 'sub_user_id', null, 'id')->orderBy('full_name', 'asc');
    }


    public function work_calendar()
    {
        return $this->hasMany(UserWorkCalendar::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    /**
     * Флаг: скрытый админ под чужим профилем?
     *
     * @return bool
     */
    public function silentAdmin()
    {
        return Session::has('mask_admin');
    }

    /**
     * Аватар
     *
     * @param $size
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed|string
     */
    public function avatar($size = 100)
    {
        return !empty($this->personal_photo[$size]) ? '/storage/' . $this->personal_photo[$size] : config('settings.user_avatar_default');
    }

    /**
     * Scope search для поиска
     *
     * @param Builder $builder
     * @param $search
     * @return Builder
     */
    public function scopeSearch(Builder $builder, $search)
    {
        $words = collect(explode(" ", $search));
        $builder->where(function ($builder) use ($words) {
            foreach ($this->searchable as $i => $field) {
                $builder->orWhere(function ($builder) use ($words, $field) {
                    $words->each(fn($item) => $builder->where($field, 'LIKE', '%' . $item . '%'));
                });
            }
        });

        return $builder;
    }



    /*
     *  "IS" block
     *
     */

    /**
     * Флаг: админ?
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->is_admin || $this->can_do('super_user');
    }

    /**
     * Флаг: доступ в админ-панель (patch v28).
     *
     * Только колонка is_admin — без super_user: суперпользователь даёт права
     * на страницы портала, а не управление пользователями и константами.
     *
     * @return bool
     */
    public function isPanelAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Флаг: может переключать тему оформления (patch v28).
     * До миграции колонки нет — тогда ведём себя как раньше: можно.
     *
     * @return bool
     */
    public function canSwitchUiTheme(): bool
    {
        return (bool) ($this->ui_theme_switch ?? true);
    }

    /**
     * Флаг: видит журнал изменений сущностей (patch v29).
     * Администраторы панели — всегда, остальные — по флагу users.log_view
     *
     * @return bool
     */
    public function canViewEntityLog(): bool
    {
        return $this->isPanelAdmin() || (bool) ($this->log_view ?? false);
    }


    /**
     * Свойство is_online для отображения пользователя на сайте
     *
     * @param $value
     * @return bool
     */
    public function getIsOnlineAttribute($value)
    {
        return $this->last_hit_at ? $this->last_hit_at->diffInSeconds() < 60 * 10 : false;
    }

    /**
     * Перезагрузка доступов
     *
     * @return void
     */
    public function can_recalc()
    {
        if (empty($this->access_service))
            $this->access_service = new AccessUserService($this->id);
        $this->access_service->refresh();
    }

    /**
     * Проверка доступа
     *
     * @param $access
     * @param $from
     * @return bool|mixed|null
     */
    public function can_do($access, $from = null)
    {
        if (empty($from)) {
            if (empty($this->access_service))
                $this->access_service = new AccessUserService($this->id);

            return $this->access_service->can_do($access);
        }

        $t = $access;
        if (!$access instanceof Access) {
            $access_input = $access;
            $access = Access::where('code', $access)->first();
        }
        if (empty($access))
            return false;

        // Если админ, дадим доступ (в зависимости от нужно поведения)
        if ($access?->id != 6 && $this->isAdmin()) {
            return $access->admin_invert == 0;
        }

        // Персональное назначение. Раньше следом шли ветки GROUP и DEPARTMENT,
        // причём результат этого запроса они затирали, и метод всегда возвращал
        // null. Модули UserGroup и UserDepartment удалены, ветки убраны —
        // возвращается сохранённый режим самого пользователя.
        return DB::table('users')
            ->select('mode')
            ->where('id', $this->id)
            ->leftJoin('access_user', 'id', '=', 'user_id')
            ->where('access_id', $access->id)
            ->value('mode');
    }

    /**
     * Обновление даты последней просмотренной страницы
     *
     * @return void
     */
    public function hit()
    {
        $this->update(['last_hit_at' => now()]);
    }

    /**
     * Вывести поля для отображения
     *
     * @return mixed
     */
    public static function getShowFields(): mixed
    {
        return self::$showFields;
    }

    /**
     * Свойство full_name с полным именем
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return trim($this->name . " " . $this->last_name . " " . $this->second_name);
    }

    /**
     * Свойство full_name_document с полным именем для вывода в документах
     *
     * @return string
     */
    public function getFullNameDocumentAttribute()
    {
        return Str::substr($this->name, 0, 1) . "." . Str::substr($this->second_name, 0, 1) . ". " . $this->last_name;
    }
//    public function getFullNameDocumentAttribute()
//    {
//        return Str::substr($this->name, 0, 1) . "." . Str::substr($this->second_name, 0, 1) . ". " . $this->last_name;
//    }
}
