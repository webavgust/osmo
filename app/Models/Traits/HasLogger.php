<?php

namespace App\Models\Traits;

use App\Modules\Pub\EntityLog\Services\EntityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Журнал изменений сущностей (patch v29).
 *
 * Подключается в модели явно: `use HasLogger;`. Модель либо корень агрегата
 * (перечислена в config/entity_log.php: КП, партнёр, компания), либо его часть —
 * тогда logParentRelation() называет belongsTo-связь на родителя, и по цепочке
 * родителей модель поднимается к корню (EntityLogService::rootOf()).
 *
 * События модели: creating/updating/deleting — если у корня ещё нет ни одного
 * слепка, он снимается прямо сейчас (иначе первый дифф не с чем сравнить);
 * created/updated/deleted — корень помечается «грязным» до конца запроса.
 * В конце запроса EntityLogService::flush() снимает новый слепок агрегата,
 * сравнивает с предыдущим и пишет entity_logs + entity_log_changes.
 * Любая ошибка журнала гасится report(): сайт от журнала падать не должен.
 *
 * Все методы настройки имеют значения по умолчанию и переопределяются в модели:
 *  - logParentRelation(): имя belongsTo-связи на родителя (null у корня);
 *  - logParent(): сам родитель (переопределяется, когда связь не belongsTo);
 *  - logChildren(): ['variants' => ProposalVariant::class, …] — hasMany-связи детей;
 *  - logFields(): подписи и типы полей, формат:
 *      'name'       => ['label' => 'Название'],
 *      'partner_id' => ['label' => 'Партнёр', 'relation' => 'partner', 'title' => 'name'],
 *      'status'     => ['label' => 'Статус', 'enum' => ProposalStatus::class],
 *      'type'       => ['label' => 'Тип', 'options' => ['a' => 'А', 'b' => 'Б']],
 *      'sended_at'  => ['label' => 'Дата', 'type' => 'date'],      // date|datetime|bool|money|html|json
 *      'group'      => ['label' => 'КП', 'format' => fn($value) => '…'],
 *    тип неописанного поля берётся из $casts, подпись — имя колонки;
 *  - logIgnore(): технические поля, которых нет в диффе (id, created_at, updated_at,
 *    deleted_at и FK родителя добавляются автоматически — logIgnoreAll());
 *  - logSharedFields(): поля, общие для всех строк группы (у КП — статус и сделка);
 *  - logKey(int $index): ключ для сопоставления детей в диффе (id либо позиция '#n');
 *  - logTitle(?int $index): подпись объекта («КП № 12 (ред. 2)», «Вариант 2 (1 год)»);
 *  - logLabel(): название типа объекта («Нейросервис»);
 *  - logUrl(): детальная страница (только у корня);
 *  - logGroupKey(): ключ ленты (у КП — group, общий для редакций);
 *  - logType(): слаг типа (proposal_variant_scenario) — используется в фильтре по полю;
 *  - logFindByGroupKey(): корень по ключу ленты; logPredecessor(): предшественник
 *    для события created (у КП — предыдущая редакция).
 */
trait HasLogger
{
    /**
     * Слушатели событий модели
     *
     * @return void
     */
    public static function bootHasLogger(): void
    {
        foreach (['creating', 'updating', 'deleting'] as $event) {
            static::{$event}(function (Model $model) {
                static::logGuard(fn() => EntityLogService::before($model));
            });
        }

        foreach (['created', 'updated', 'deleted'] as $event) {
            static::{$event}(function (Model $model) use ($event) {
                static::logGuard(fn() => EntityLogService::after($model, $event));
            });
        }
    }

    /**
     * Любая ошибка журнала гасится: сайт от него падать не должен
     *
     * @param callable $fn
     * @return void
     */
    protected static function logGuard(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Имя belongsTo-связи на родителя в агрегате (null — корень)
     *
     * @return string|null
     */
    public static function logParentRelation(): ?string
    {
        return null;
    }

    /**
     * Модель — корень агрегата (перечислена в config/entity_log.php).
     * Связи при этом не загружаются.
     *
     * @return bool
     */
    public static function isLogRoot(): bool
    {
        return EntityLogService::isRootClass(static::class);
    }

    /**
     * Родитель в агрегате; null — корень либо сирота (FK пустой)
     *
     * @return Model|null
     */
    public function logParent(): ?Model
    {
        if (static::isLogRoot()) return null;

        $relation = static::logParentRelation();

        return $relation ? EntityLogService::parentVia($this, $relation) : null;
    }

    /**
     * У части меняется FK родителя (в событиях updating/updated original ещё старый):
     * кэш корня в EntityLogService::rootOf() при этом не используется
     *
     * @return bool
     */
    public function logParentChanged(): bool
    {
        $relation = static::logParentRelation();
        if (!$relation || !$this->exists) return false;

        return $this->isDirty($this->{$relation}()->getForeignKeyName());
    }

    /**
     * Старый родитель, если у части меняется FK родителя (событие updating):
     * его корень тоже получит событие
     *
     * @return Model|null
     */
    public function logOldParent(): ?Model
    {
        $relation = static::logParentRelation();
        if (!$this->logParentChanged()) return null;

        $original = $this->getOriginal($this->{$relation}()->getForeignKeyName());
        if ($original === null || $original === '') return null;

        return EntityLogService::parentVia($this, $relation, $original);
    }

    /**
     * Дети агрегата: имя hasMany-связи => класс
     *
     * @return array
     */
    public static function logChildren(): array
    {
        return [];
    }

    /**
     * Подписи и типы полей (см. docblock trait)
     *
     * @return array
     */
    public static function logFields(): array
    {
        return [];
    }

    /**
     * Технические поля, которых нет в диффе
     *
     * @return array
     */
    public static function logIgnore(): array
    {
        return [];
    }

    /**
     * Полный список игнорируемых полей: свои + id, метки времени, FK родителя
     *
     * @return array
     */
    public static function logIgnoreAll(): array
    {
        $model = new static;

        $ignore = array_merge([
            $model->getKeyName(),
            $model->getCreatedAtColumn() ?: 'created_at',
            $model->getUpdatedAtColumn() ?: 'updated_at',
            'deleted_at',
        ], static::logIgnore());

        $relation = static::logParentRelation();
        if ($relation) {
            $ignore[] = $model->{$relation}()->getForeignKeyName();
        }

        return array_values(array_unique(array_filter($ignore)));
    }

    /**
     * Поля, общие для всех строк группы ленты (у КП — статус и сделка пишутся
     * во все редакции): старое значение берётся из последнего слепка группы,
     * чтобы правка старой редакции не показывала чужую смену статуса
     *
     * @return array
     */
    public static function logSharedFields(): array
    {
        return [];
    }

    /**
     * Ключ для сопоставления детей в диффе: id либо позиция в связи ('#n')
     *
     * @param int $index позиция в коллекции связи, с нуля
     * @return string
     */
    public function logKey(int $index): string
    {
        return (string) $this->getKey();
    }

    /**
     * Связи belongsTo, которые при просмотре состояния на дату берутся из слепка,
     * а не из базы: ['имя связи' => Класс::class].
     *
     * Нужно для строк, на которые ссылается ребёнок агрегата (позиция варианта →
     * строка ПО или работы самого КП): если такую строку потом удалили, живой
     * загрузкой связь вернёт null и страница состояния упадёт
     *
     * @return array
     */
    public static function logLinks(): array
    {
        return [];
    }

    /**
     * Позиционный ключ '#n' — для коллекций, которые пересоздаются при каждом сохранении
     *
     * @param int $index
     * @return string
     */
    public function logPositionKey(int $index): string
    {
        return '#' . ($index + 1);
    }

    /**
     * Подпись объекта: name / full_name / title / number / «Тип #id»
     *
     * @param int|null $index номер среди детей родителя (с единицы), если известен
     * @return string
     */
    public function logTitle(?int $index = null): string
    {
        foreach (['name', 'full_name', 'title', 'number'] as $field) {
            $value = $this->getAttribute($field);

            if (is_string($value) && trim(strip_tags($value)) !== '') {
                return trim(strip_tags($value));
            }
        }

        return static::logLabel() . ' #' . $this->getKey();
    }

    /**
     * Название типа объекта
     *
     * @return string
     */
    public static function logLabel(): string
    {
        return class_basename(static::class);
    }

    /**
     * Детальная страница (только у корня)
     *
     * @return string|null
     */
    public function logUrl(): ?string
    {
        return null;
    }

    /**
     * У корня есть страница, умеющая показать состояние на дату (?at=). У проекта по
     * сделкам её нет — карточка проекта попап, и ссылки «состояние на момент» в ленте не
     * выводятся
     *
     * @return bool
     */
    public static function logStateView(): bool
    {
        return true;
    }

    /**
     * Ключ ленты журнала
 (у КП — group, общий для редакций)
     *
     * @return string
     */
    public function logGroupKey(): string
    {
        return (string) $this->getKey();
    }

    /**
     * Слаг типа: proposal, proposal_variant_scenario, …
     *
     * @return string
     */
    public static function logType(): string
    {
        return Str::snake(class_basename(static::class));
    }

    /**
     * Корень по ключу ленты (у КП — последняя редакция группы)
     *
     * @param string $key
     * @return Model|null
     */
    public static function logFindByGroupKey(string $key): ?Model
    {
        return static::query()->find($key);
    }

    /**
     * Предшественник для события created: его последний слепок становится
     * базой диффа (у КП — предыдущая редакция группы)
     *
     * @return Model|null
     */
    public function logPredecessor(): ?Model
    {
        return null;
    }

    /**
     * Подпись поля из logFields() либо имя колонки
     *
     * @param string $field
     * @return string
     */
    public static function logFieldLabel(string $field): string
    {
        return static::logFields()[$field]['label'] ?? $field;
    }

    /**
     * Текст без разметки, обрезанный до $limit символов — для подписей объектов
     *
     * @param mixed $value
     * @param int $limit
     * @return string
     */
    public static function logText($value, int $limit = 60): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value))));

        return Str::limit($text, $limit);
    }
}
