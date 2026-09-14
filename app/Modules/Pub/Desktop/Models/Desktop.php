<?php

namespace App\Modules\Pub\Desktop\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Рабочий стол (patch v30).
 *
 * Стол пользователя (user_id заполнен) либо системный пресет (is_system = 1,
 * user_id = null): его видят все, правит только админ (User::isPanelAdmin()).
 * context — значения по умолчанию для виджетов: ['currency' => 'RUB', 'period' => 'quarter'].
 */
class Desktop extends ModuleModel
{
    public static $module_name = 'Рабочий стол';
    public static $detail_route = 'desktop.index';

    protected $table = 'desktops';
    protected $fillable = [
        'user_id', 'name', 'is_system', 'is_default', 'sort', 'context',
        'source_id', 'source_version', 'version', 'created_by', 'updated_by',
    ];
    protected $casts = [
        'is_system' => 'bool',
        'is_default' => 'bool',
        'context' => 'array',
        'sort' => 'int',
        'version' => 'int',
        'source_version' => 'int',
    ];

    /*** RELATIONS ***/

    public function widgets()
    {
        return $this->hasMany(DesktopWidget::class)->orderBy('y')->orderBy('x');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /** Системный пресет, из которого скопирован стол */
    public function source()
    {
        return $this->belongsTo(Desktop::class, 'source_id');
    }

    /*** SCOPES ***/

    /**
     * Личные столы пользователя
     *
     * @param Builder $builder
     * @param User|int $user
     * @return Builder
     */
    public function scopeOwnedBy(Builder $builder, User|int $user)
    {
        return $builder->where('user_id', $user instanceof User ? $user->id : $user)
            ->where('is_system', false);
    }

    /**
     * Системные пресеты
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeSystem(Builder $builder)
    {
        return $builder->where('is_system', true);
    }

    /*** ACCESS ***/

    /**
     * Может ли пользователь открыть стол: свой или системный
     *
     * @param User $user
     * @return bool
     */
    public function canView(User $user): bool
    {
        return $this->is_system || (int) $this->user_id === (int) $user->id;
    }

    /**
     * Может ли пользователь менять стол: свой; системный — только админ
     *
     * @param User $user
     * @return bool
     */
    public function canEdit(User $user): bool
    {
        return $this->is_system
            ? $user->isPanelAdmin()
            : (int) $this->user_id === (int) $user->id;
    }

    /**
     * Контекст стола (валюта и период по умолчанию)
     *
     * @param User|null $user
     * @return DesktopContext
     */
    public function contextObject(?User $user = null): DesktopContext
    {
        return DesktopContext::make((array) $this->context, $user);
    }
}
