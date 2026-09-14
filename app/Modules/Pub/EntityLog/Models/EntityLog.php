<?php

namespace App\Modules\Pub\EntityLog\Models;

use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Событие журнала изменений (patch v29): слепок корня агрегата.
 *
 * event: baseline (первый слепок, без изменений), created, updated, deleted
 * (data = null). type — слаг корня, group_key — ключ ленты (у КП — group),
 * model_id — id строки (у КП — редакции).
 */
class EntityLog extends Model
{
    public const EVENT_BASELINE = 'baseline';
    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_DELETED = 'deleted';

    /** Подписи событий для ленты */
    public const EVENTS = [
        self::EVENT_BASELINE => 'Начало журнала',
        self::EVENT_CREATED => 'Создание',
        self::EVENT_UPDATED => 'Изменение',
        self::EVENT_DELETED => 'Удаление',
    ];

    protected $table = 'entity_logs';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'model_id' => 'int',
        'user_id' => 'int',
        'changes_count' => 'int',
        'created_at' => 'datetime',
    ];

    /*** RELATIONS ***/

    /** Кто внёс изменение (withTrashed: имя не пропадает у мягко удалённого) */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /** Строки изменений */
    public function changes()
    {
        return $this->hasMany(EntityLogChange::class, 'entity_log_id')->orderBy('id');
    }

    /*** SCOPES ***/

    /**
     * Лента одного объекта
     *
     * @param Builder $builder
     * @param string $type
     * @param string $key
     * @return Builder
     */
    public function scopeTimeline(Builder $builder, string $type, string $key)
    {
        return $builder->where('type', $type)->where('group_key', $key);
    }

    /**
     * Только события со слепком (без deleted)
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeWithSnapshot(Builder $builder)
    {
        return $builder->whereNotNull('data');
    }

    /*** ATTRIBUTES ***/

    /**
     * Слепок в виде массива
     *
     * @return array|null
     */
    public function getDataArrayAttribute(): ?array
    {
        if ($this->data === null) return null;

        $data = json_decode($this->data, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Подпись события
     *
     * @return string
     */
    public function getEventLabelAttribute(): string
    {
        return static::EVENTS[$this->event] ?? $this->event;
    }

    /**
     * Класс корня по слагу
     *
     * @return string|null
     */
    public function getRootClassAttribute(): ?string
    {
        return EntityLogService::classOf($this->type);
    }

    /**
     * Живой корень (текущее состояние); null, если удалён
     *
     * @return Model|null
     */
    public function root(): ?Model
    {
        $class = $this->root_class;

        return $class ? $class::query()->find($this->model_id) : null;
    }
}
