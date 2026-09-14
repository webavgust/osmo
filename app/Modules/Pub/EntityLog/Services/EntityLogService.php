<?php

namespace App\Modules\Pub\EntityLog\Services;

use App\Modules\Pub\EntityLog\Models\EntityLog;
use App\Modules\Pub\EntityLog\Models\EntityLogChange;
use App\Modules\Pub\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Журнал изменений сущностей (patch v29): ядро.
 *
 * Жизненный цикл в запросе:
 *  - before($model) из событий creating/updating/deleting — найти корень
 *    агрегата; если у него ещё нет слепка (и он не создан в этом запросе) —
 *    снять baseline сейчас; пометить корень грязным. При смене FK родителя
 *    у части грязным становится и старый корень;
 *  - after($model) из событий created/updated/deleted — пометить корень грязным
 *    (созданный корень запоминается как «свежий»);
 *  - around($root, $fn) — обёртка для массовых update/delete без событий;
 *  - flush() в конце запроса (middleware FlushEntityLog и app()->terminating):
 *    по каждому грязному корню — новый слепок, дифф с последним слепком,
 *    запись entity_logs + entity_log_changes. Пустой дифф не пишется.
 *
 * Чтение: timeline(), fieldOptions(), userOptions(), stateAt(), rootByKey().
 * Ошибки журнала гасятся report(): сайт от журнала падать не должен.
 */
class EntityLogService
{
    /** Грязные корни на текущий запрос: 'Class:key' => Model */
    protected static array $dirty = [];

    /** Кэш корней частей: 'Class:key' => Model|null */
    protected static array $roots = [];

    /** Кэш родителей по FK: 'Class:owner=value' => Model|null */
    protected static array $parents = [];

    /** Признак «у корня есть слепок»: 'type:id' => bool */
    protected static array $has_snapshot = [];

    /** Корни, созданные в этом запросе: 'Class:key' => true */
    protected static array $fresh = [];

    /** Реестр классов агрегатов: слаг => класс */
    protected static ?array $classes = null;

    protected static bool $registered = false;

    /*** РЕГИСТРАЦИЯ ***/

    /**
     * Страховочный flush при завершении приложения (консоль, ошибки).
     * Вызывается из AppServiceProvider::boot()
     *
     * @return void
     */
    public static function register(): void
    {
        if (static::$registered) return;
        static::$registered = true;

        app()->terminating(fn() => static::flush());
    }

    /**
     * Корни агрегатов: слаг => класс (config/entity_log.php)
     *
     * @return array
     */
    public static function types(): array
    {
        return (array) config('entity_log.types', []);
    }

    /**
     * Класс корня по слагу
     *
     * @param string $type
     * @return string|null
     */
    public static function classOf(string $type): ?string
    {
        return static::types()[$type] ?? null;
    }

    /**
     * Класс — корень агрегата
     *
     * @param string $class
     * @return bool
     */
    public static function isRootClass(string $class): bool
    {
        return in_array($class, static::types(), true);
    }

    /**
     * Все классы агрегатов (корни и части, обход logChildren()): слаг => класс.
     * Порядок — обход в глубину от корней; им упорядочены группы fieldOptions()
     *
     * @return array
     */
    public static function classes(): array
    {
        if (static::$classes !== null) return static::$classes;

        $out = [];
        $walk = function (string $class) use (&$walk, &$out) {
            if (!method_exists($class, 'logType')) return;

            $slug = $class::logType();
            if (isset($out[$slug])) return;
            $out[$slug] = $class;

            foreach ($class::logChildren() as $child) {
                $walk($child);
            }
        };

        foreach (static::types() as $class) {
            $walk($class);
        }

        return static::$classes = $out;
    }

    /**
     * Класс по слагу типа (любой уровень агрегата)
     *
     * @param string $slug
     * @return string|null
     */
    public static function classBySlug(string $slug): ?string
    {
        return static::classes()[$slug] ?? null;
    }

    /**
     * Корень по ключу ленты (у КП — последняя редакция группы)
     *
     * @param string $type
     * @param string $key
     * @return Model|null
     */
    public static function rootByKey(string $type, string $key): ?Model
    {
        $class = static::classOf($type);

        return $class ? $class::logFindByGroupKey($key) : null;
    }

    /*** ПОДЪЁМ К КОРНЮ ***/

    /**
     * Корень агрегата модели: подъём по logParent() с кэшем на запрос.
     * Корень возвращается как есть (в том числе ещё не созданный, без id).
     *
     * @param Model $model
     * @return Model|null null — сирота (FK родителя пустой или родитель удалён)
     */
    public static function rootOf(Model $model): ?Model
    {
        if ($model::isLogRoot()) {
            $key = static::keyOf($model);

            return $key !== null && isset(static::$roots[$key]) ? static::$roots[$key] : $model;
        }

        // пока FK родителя грязный, кэш не используется: корень пересчитывается и перезаписывается
        $key = static::keyOf($model);
        $changed = method_exists($model, 'logParentChanged') && $model->logParentChanged();

        if ($key !== null && !$changed && array_key_exists($key, static::$roots)) {
            return static::$roots[$key];
        }

        $parent = $model->logParent();
        $root = $parent ? static::rootOf($parent) : null;

        if ($key !== null) {
            static::$roots[$key] = $root;
        }

        return $root;
    }

    /**
     * Родитель по belongsTo-связи с кэшем по значению FK
     *
     * @param Model $model
     * @param string $relation имя связи
     * @param mixed $value значение FK; null — текущее значение модели
     * @return Model|null
     */
    public static function parentVia(Model $model, string $relation, $value = null): ?Model
    {
        $query = $model->{$relation}();
        $related = $query->getRelated();
        $owner = method_exists($query, 'getOwnerKeyName') ? $query->getOwnerKeyName() : $related->getKeyName();
        $value = $value ?? $model->getAttribute($query->getForeignKeyName());

        if ($value === null || $value === '') return null;

        $cache = get_class($related) . ':' . $owner . '=' . $value;

        if (!array_key_exists($cache, static::$parents)) {
            static::$parents[$cache] = $related->newQuery()->where($owner, $value)->first();
        }

        return static::$parents[$cache];
    }

    /*** СОБЫТИЯ ***/

    /**
     * До записи модели: baseline корня при отсутствии слепков + пометка грязным.
     * При смене FK родителя у части обрабатывается и старый корень
     *
     * @param Model $model
     * @return void
     */
    public static function before(Model $model): void
    {
        $root = static::rootOf($model);
        if ($root) static::prepare($root);

        if ($model->exists && method_exists($model, 'logOldParent')) {
            $old = $model->logOldParent();
            $old_root = $old ? static::rootOf($old) : null;

            if ($old_root && !static::same($old_root, $root)) {
                static::prepare($old_root);
            }
        }
    }

    /**
     * После записи модели: пометить корень грязным; созданный корень — «свежий»
     * (baseline ему не снимается, событие будет created)
     *
     * @param Model $model
     * @param string $event created|updated|deleted
     * @return void
     */
    public static function after(Model $model, string $event = 'updated'): void
    {
        if ($event === 'created' && $model::isLogRoot() && $model->getKey() !== null) {
            $key = static::keyOf($model);
            static::$fresh[$key] = true;
            static::$roots[$key] = $model;
        }

        $root = static::rootOf($model);
        if ($root) static::touch($root);
    }

    /**
     * Пометить корень грязным: в конце запроса по нему будет снят слепок
     *
     * @param Model $root корень (часть агрегата тоже допустима — поднимется к корню)
     * @return void
     */
    public static function touch(Model $root): void
    {
        $root = $root::isLogRoot() ? $root : static::rootOf($root);
        if (empty($root) || $root->getKey() === null) return;

        static::$dirty[static::keyOf($root)] = $root;
    }

    /**
     * Обёртка для массовых update/delete без событий модели:
     * baseline при отсутствии + пометка грязным до и после
     *
     * @param Model|null $root null — просто выполнить
     * @param callable $fn
     * @return mixed результат $fn
     */
    public static function around(?Model $root, callable $fn): mixed
    {
        if ($root) {
            try {
                static::before($root);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $result = $fn();

        if ($root) {
            try {
                static::touch($root);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $result;
    }

    /**
     * Записать события по всем грязным корням
     *
     * @return void
     */
    public static function flush(): void
    {
        while (!empty(static::$dirty)) {
            $key = array_key_first(static::$dirty);
            $root = static::$dirty[$key];
            unset(static::$dirty[$key]);

            try {
                static::write($root);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Baseline-слепок корня (без строк изменений)
     *
     * @param Model $root
     * @return EntityLog|null null — корня нет в базе
     */
    public static function baseline(Model $root): ?EntityLog
    {
        $fresh = static::reload($root);
        if (empty($fresh)) return null;

        return static::store($fresh, EntityLog::EVENT_BASELINE, EntityLogSnapshot::make($fresh), []);
    }

    /*** ЗАПИСЬ ***/

    /**
     * Baseline при отсутствии слепков (кроме созданных в этом запросе) + пометка грязным
     *
     * @param Model $root
     * @return void
     */
    protected static function prepare(Model $root): void
    {
        if ($root->getKey() === null) return;

        if (!static::isFresh($root) && !static::hasSnapshot($root)) {
            static::baseline($root);
        }

        static::touch($root);
    }

    /**
     * Событие по корню: слепок, дифф с предыдущим, запись
     *
     * @param Model $root
     * @return EntityLog|null
     */
    protected static function write(Model $root): ?EntityLog
    {
        $type = $root::logType();
        $id = $root->getKey();
        $fresh = static::reload($root);

        if (empty($fresh)) {
            // удалён: событие только если корень уже был в журнале
            if (!EntityLog::where('type', $type)->where('model_id', $id)->exists()) return null;

            return static::store($root, EntityLog::EVENT_DELETED, null, []);
        }

        $data = EntityLogSnapshot::make($fresh);
        $last = static::lastLog($type, $id);

        if (empty($last)) {
            $predecessor = $fresh->logPredecessor();
            $base = $predecessor ? static::lastLog($predecessor::logType(), $predecessor->getKey()) : null;

            if ($base) {
                return static::store($fresh, EntityLog::EVENT_CREATED, $data, EntityLogDiff::compare($base->data_array, $data));
            }

            if (static::isFresh($fresh)) {
                return static::store($fresh, EntityLog::EVENT_CREATED, $data, EntityLogDiff::compare(null, $data));
            }

            // существующий корень без слепков (baseline ещё не снимали) — просто baseline
            return static::store($fresh, EntityLog::EVENT_BASELINE, $data, []);
        }

        $rows = EntityLogDiff::compare(static::overlayShared($fresh, $last), $data);
        if (empty($rows)) return null;

        return static::store($fresh, EntityLog::EVENT_UPDATED, $data, $rows);
    }

    /**
     * Старый слепок с поправкой на общие поля группы (logSharedFields()):
     * их старое значение — из последнего слепка группы, чтобы правка старой
     * редакции КП не показывала чужую смену статуса
     *
     * @param Model $root
     * @param EntityLog $last последний слепок этой строки
     * @return array|null
     */
    protected static function overlayShared(Model $root, EntityLog $last): ?array
    {
        $old = $last->data_array;
        $shared = $root::logSharedFields();
        if (empty($shared) || empty($old)) return $old;

        $latest = EntityLog::timeline($root::logType(), $root->logGroupKey())
            ->withSnapshot()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (empty($latest) || (int) $latest->model_id === (int) $root->getKey() || $latest->id === $last->id) return $old;

        $attrs = (array) ($latest->data_array['attrs'] ?? []);
        foreach ($shared as $field) {
            if (array_key_exists($field, $attrs)) {
                $old['attrs'][$field] = $attrs[$field];
            }
        }

        return $old;
    }

    /**
     * Записать событие и строки изменений
     *
     * @param Model $root
     * @param string $event
     * @param array|null $data слепок (null у deleted)
     * @param array $rows строки EntityLogDiff::compare()
     * @return EntityLog
     */
    protected static function store(Model $root, string $event, ?array $data, array $rows): EntityLog
    {
        $log = new EntityLog([
            'type' => $root::logType(),
            'group_key' => mb_substr($root->logGroupKey(), 0, 64),
            'model_id' => $root->getKey(),
            'event' => $event,
            'user_id' => auth()->id(),
            'title' => mb_substr((string) $root->logTitle(), 0, 255),
            'data' => $data === null ? null : EntityLogSnapshot::encode($data),
            'changes_count' => count($rows),
            'created_at' => now(),
        ]);
        $log->save();

        if (!empty($rows)) {
            EntityLogChange::insert(array_map(fn($row) => $row + ['entity_log_id' => $log->id], $rows));
        }

        static::$has_snapshot[$log->type . ':' . $log->model_id] = $data !== null;

        return $log;
    }

    /**
     * Последний слепок строки
     *
     * @param string $type
     * @param mixed $id
     * @return EntityLog|null
     */
    protected static function lastLog(string $type, $id): ?EntityLog
    {
        return EntityLog::where('type', $type)
            ->where('model_id', $id)
            ->withSnapshot()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * У корня есть слепок (кэш на запрос)
     *
     * @param Model $root
     * @return bool
     */
    protected static function hasSnapshot(Model $root): bool
    {
        $key = $root::logType() . ':' . $root->getKey();

        if (!array_key_exists($key, static::$has_snapshot)) {
            static::$has_snapshot[$key] = EntityLog::where('type', $root::logType())
                ->where('model_id', $root->getKey())
                ->withSnapshot()
                ->exists();
        }

        return static::$has_snapshot[$key];
    }

    /**
     * Корень создан в этом запросе
     *
     * @param Model $root
     * @return bool
     */
    protected static function isFresh(Model $root): bool
    {
        $key = static::keyOf($root);

        return $key !== null && !empty(static::$fresh[$key]);
    }

    /**
     * Свежая строка корня из базы (null — удалён)
     *
     * @param Model $root
     * @return Model|null
     */
    protected static function reload(Model $root): ?Model
    {
        if ($root->getKey() === null) return null;

        return $root->newQuery()->whereKey($root->getKey())->first();
    }

    /**
     * Ключ кэша модели: 'Class:key'; null у несохранённой
     *
     * @param Model $model
     * @return string|null
     */
    protected static function keyOf(Model $model): ?string
    {
        $key = $model->getKey();

        return $key === null ? null : get_class($model) . ':' . $key;
    }

    /**
     * Одна и та же строка
     *
     * @param Model|null $a
     * @param Model|null $b
     * @return bool
     */
    protected static function same(?Model $a, ?Model $b): bool
    {
        return $a && $b && get_class($a) === get_class($b) && (string) $a->getKey() === (string) $b->getKey();
    }

    /*** ЧТЕНИЕ ***/

    /**
     * Лента изменений объекта: события по убыванию времени с user и changes.
     *
     * Фильтры: field — 'слаг_типа.поле' (proposal_variant_scenario.count):
     * в выдаче остаются события, где есть такая строка, и в changes — только
     * подходящие строки (changes_count при этом хранит общее число);
     * user — id пользователя; event — baseline|created|updated|deleted.
     *
     * @param string $type
     * @param string $key
     * @param array $filter ['field' => ..., 'user' => ..., 'event' => ...]
     * @param int $per_page
     * @return LengthAwarePaginator
     */
    public static function timeline(string $type, string $key, array $filter = [], int $per_page = 30): LengthAwarePaginator
    {
        $query = EntityLog::timeline($type, $key)
            // «изменено» без строк — перестановка без смысловых правок (после entity-log:rediff)
            ->where(fn($builder) => $builder->where('event', '!=', EntityLog::EVENT_UPDATED)->orWhere('changes_count', '>', 0))
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $field = static::parseField($filter['field'] ?? null);

        if ($field) {
            $where = fn($builder) => $builder->where('model_class', $field['class'])->where('field', $field['field']);
            $query->whereHas('changes', $where)->with(['changes' => $where]);
        } else {
            $query->with('changes');
        }

        if (!empty($filter['user'])) {
            $query->where('user_id', (int) $filter['user']);
        }

        if (!empty($filter['event'])) {
            $query->where('event', (string) $filter['event']);
        }

        return $query->paginate($per_page)->withQueryString();
    }

    /**
     * Поля, встречающиеся в изменениях ленты, сгруппированные по типу объекта
     * (для select с optgroup): [['group' => 'КП', 'options' => [['value' => 'proposal.name', 'label' => 'Название'], …]], …]
     *
     * @param string $type
     * @param string $key
     * @return array
     */
    public static function fieldOptions(string $type, string $key): array
    {
        $pairs = EntityLogChange::query()
            ->whereIn('entity_log_id', EntityLog::timeline($type, $key)->select('id'))
            ->whereNotNull('field')
            ->select(['model_class', 'field'])
            ->distinct()
            ->get();

        if ($pairs->isEmpty()) return [];

        $order = array_flip(array_values(static::classes()));

        return $pairs
            ->groupBy('model_class')
            ->sortBy(fn($rows, $class) => $order[$class] ?? PHP_INT_MAX)
            ->map(function ($rows, $class) {
                $declared = method_exists($class, 'logFields') ? array_flip(array_keys($class::logFields())) : [];
                $slug = method_exists($class, 'logType') ? $class::logType() : \Illuminate\Support\Str::snake(class_basename($class));

                $options = $rows
                    ->map(fn($row) => [
                        'value' => $slug . '.' . $row->field,
                        'label' => method_exists($class, 'logFieldLabel') ? $class::logFieldLabel($row->field) : $row->field,
                        'sort' => $declared[$row->field] ?? PHP_INT_MAX,
                    ])
                    ->sortBy([['sort', 'asc'], ['label', 'asc']])
                    ->map(fn($row) => ['value' => $row['value'], 'label' => $row['label']])
                    ->values()
                    ->all();

                return [
                    'group' => method_exists($class, 'logLabel') ? $class::logLabel() : class_basename($class),
                    'options' => $options,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Пользователи, вносившие изменения в ленте: [['id' => 1, 'name' => 'Анна …'], …]
     *
     * @param string $type
     * @param string $key
     * @return array
     */
    public static function userOptions(string $type, string $key): array
    {
        $ids = EntityLog::timeline($type, $key)->whereNotNull('user_id')->distinct()->pluck('user_id');
        if ($ids->isEmpty()) return [];

        return User::withTrashed()
            ->whereIn('id', $ids->all())
            ->get()
            ->map(fn(User $user) => ['id' => (int) $user->id, 'name' => trim((string) $user->full_name) ?: ('#' . $user->id)])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Состояние корня на момент: гидрированная модель из слепка.
     *
     * $at — id слепка (число; проверяется принадлежность этому корню) либо дата
     * Y-m-d (последний слепок на 23:59:59 этой даты; можно и полную дату-время).
     * Раньше первого слепка — первый слепок, exact = false и note с пояснением.
     * У КП слепки ищутся по конкретной редакции (type + model_id).
     *
     * @param Model $root
     * @param string|int $at
     * @return array|null ['model' => Model, 'log' => EntityLog, 'exact' => bool, 'note' => string|null, 'at' => Carbon]
     */
    public static function stateAt(Model $root, string|int $at): ?array
    {
        $query = EntityLog::where('type', $root::logType())
            ->where('model_id', $root->getKey())
            ->withSnapshot();

        $exact = true;
        $note = null;

        if (is_int($at) || ctype_digit((string) $at)) {
            $log = (clone $query)->whereKey((int) $at)->first();
            if (empty($log)) return null;

            $moment = $log->created_at;
        } else {
            $at = trim((string) $at);

            try {
                $moment = preg_match('/^\d{4}-\d{2}-\d{2}$/', $at)
                    ? Carbon::createFromFormat('Y-m-d', $at)->endOfDay()
                    : Carbon::parse($at);
            } catch (\Throwable $e) {
                return null;
            }

            $log = (clone $query)->where('created_at', '<=', $moment)->orderByDesc('created_at')->orderByDesc('id')->first();

            if (empty($log)) {
                $log = (clone $query)->orderBy('created_at')->orderBy('id')->first();
                if (empty($log)) return null;

                $exact = false;
                $note = 'Журнал ведётся с ' . $log->created_at->format('d.m.Y') . ', показано первое известное состояние';
            }
        }

        $data = $log->data_array;
        if (empty($data)) return null;

        return [
            'model' => EntityLogSnapshot::hydrate($data),
            'log' => $log,
            'exact' => $exact,
            'note' => $note,
            'at' => $moment,
        ];
    }

    /**
     * Разбор значения фильтра по полю: 'слаг.поле' → ['class' => …, 'field' => …]
     *
     * @param string|null $value
     * @return array|null
     */
    public static function parseField(?string $value): ?array
    {
        if (empty($value) || !preg_match('/^([a-z0-9_]+)\.([a-z0-9_]+)$/', $value, $m)) return null;

        $class = static::classBySlug($m[1]);

        return $class ? ['class' => $class, 'field' => $m[2]] : null;
    }
}
