<?php

namespace App\Modules\Pub\EntityLog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Строка изменений журнала (patch v29).
 *
 * kind: changed (поле поменялось), added / removed (объект появился или исчез —
 * одна строка на объект, field = null). path — цепочка подписей родителей,
 * у полей корня null. old_value / new_value — сырые значения, old_label /
 * new_label — человеческие, сформированные в момент записи. derived — поле пересчитал
 * сам портал следом за правкой пользователя (итоги, НДС), в ленте такие строки идут
 * под основными.
 */
class EntityLogChange extends Model
{
    public const KIND_CHANGED = 'changed';
    public const KIND_ADDED = 'added';
    public const KIND_REMOVED = 'removed';

    protected $table = 'entity_log_changes';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['derived' => 'bool'];

    /*** RELATIONS ***/

    public function log()
    {
        return $this->belongsTo(EntityLog::class, 'entity_log_id');
    }

    /*** ATTRIBUTES ***/

    /**
     * Значение для фильтра ленты по полю: слаг типа + '.' + поле
     * (например proposal_variant_scenario.count)
     *
     * @return string|null
     */
    public function getFieldKeyAttribute(): ?string
    {
        if (empty($this->field)) return null;

        $class = $this->model_class;
        $slug = class_exists($class) && method_exists($class, 'logType')
            ? $class::logType()
            : \Illuminate\Support\Str::snake(class_basename($class));

        return $slug . '.' . $this->field;
    }

    /**
     * Название типа объекта (logLabel класса)
     *
     * @return string
     */
    public function getModelLabelAttribute(): string
    {
        $class = $this->model_class;

        return class_exists($class) && method_exists($class, 'logLabel')
            ? $class::logLabel()
            : class_basename($class);
    }
}
