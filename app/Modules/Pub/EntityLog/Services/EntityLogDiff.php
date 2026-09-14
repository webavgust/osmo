<?php

namespace App\Modules\Pub\EntityLog\Services;

use App\Models\Traits\HasLogger;
use App\Modules\Pub\EntityLog\Models\EntityLogChange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Дифф двух слепков агрегата (patch v29).
 *
 * Дети сопоставляются по key (id либо позиция): совпали — сравниваются поля
 * (changed) и рекурсивно дети; нет в старом — added, нет в новом — removed
 * (одна строка на объект). Сравнение нормализованное: null и '' равны,
 * числа сравниваются как строки без хвостовых нулей (100 == 100.0).
 *
 * Человеческие подписи (old_label / new_label) считаются по logFields():
 * relation → подпись связанной модели, enum → label, options → словарь,
 * bool → да/нет, date → d.m.Y, datetime → d.m.Y H:i, money → cost_normalize,
 * html → текст без разметки, null → «—».
 */
class EntityLogDiff
{
    /** Обрезка html/json-значений в подписях */
    public const LABEL_LIMIT = 300;

    /** Подписи объектов старого и нового слепка на время compare(): класс => id => подпись */
    protected static array $titles_old = [];
    protected static array $titles_new = [];

    /**
     * Строки изменений между старым и новым слепком.
     * Старый null — объект создан: added по непустым полям корня и по детям первого уровня.
     *
     * @param array|null $old
     * @param array $new
     * @return array строки для entity_log_changes (без entity_log_id)
     */
    public static function compare(?array $old, array $new): array
    {
        static::$titles_old = $old === null ? [] : static::titles($old);
        static::$titles_new = static::titles($new);

        $rows = [];
        static::node($old, $new, [], $rows);

        static::$titles_old = static::$titles_new = [];

        return $rows;
    }

    /**
     * Сравнение одного узла
     *
     * @param array|null $old
     * @param array $new
     * @param array $path подписи родителей и самого узла (у корня пусто)
     * @param array $rows
     * @return void
     */
    protected static function node(?array $old, array $new, array $path, array &$rows): void
    {
        $class = $new['class'] ?? ($old['class'] ?? null);
        if (empty($class)) return;

        $key = (string) ($new['key'] ?? ($old['key'] ?? ''));
        $path_text = $path ? implode(' → ', $path) : null;
        $ignore = array_flip(static::ignore($class));

        $old_attrs = (array) ($old['attrs'] ?? []);
        $new_attrs = (array) ($new['attrs'] ?? []);

        foreach (array_unique(array_merge(array_keys($old_attrs), array_keys($new_attrs))) as $field) {
            if (isset($ignore[$field])) continue;

            $o = $old_attrs[$field] ?? null;
            $n = $new_attrs[$field] ?? null;

            if ($old === null) {
                // новый объект: показываем только заполненные поля
                if (static::normalize($n) === '') continue;
                $rows[] = static::row(EntityLogChange::KIND_ADDED, $class, $key, $path_text, $field, null, $n);
                continue;
            }

            if (static::normalize($o) === static::normalize($n)) continue;

            // ссылка на строку того же агрегата (logLinks): строки ПО и работ КП пересоздаются
            // при сохранении, id меняется, а объект тот же — сравниваем по подписи из слепков
            $target = static::linkTarget($class, $field);
            if ($target !== null) {
                $o_title = static::$titles_old[$target][(string) $o] ?? null;
                $n_title = static::$titles_new[$target][(string) $n] ?? null;

                if ($o_title !== null && $o_title === $n_title) continue;

                $rows[] = static::row(EntityLogChange::KIND_CHANGED, $class, $key, $path_text, $field, $o, $n, $o_title, $n_title);
                continue;
            }

            $rows[] = static::row(EntityLogChange::KIND_CHANGED, $class, $key, $path_text, $field, $o, $n);
        }

        $relations = array_unique(array_merge(
            array_keys((array) ($old['children'] ?? [])),
            array_keys((array) ($new['children'] ?? []))
        ));

        foreach ($relations as $relation) {
            $old_kids = static::keyed((array) ($old['children'][$relation] ?? []));
            $new_kids = static::keyed((array) ($new['children'][$relation] ?? []));

            [$pairs, $used] = static::pair($old, $old_kids, $new_kids);

            foreach ($new_kids as $k => $kid) {
                $kid_path = array_merge($path, [(string) ($kid['title'] ?? $k)]);

                if (isset($pairs[$k])) {
                    static::node($pairs[$k], $kid, $kid_path, $rows);
                } else {
                    $rows[] = static::objectRow(EntityLogChange::KIND_ADDED, $kid, $kid_path);
                }
            }

            if ($old === null) continue;

            foreach ($old_kids as $k => $kid) {
                if (isset($used[(string) $k])) continue;

                $rows[] = static::objectRow(EntityLogChange::KIND_REMOVED, $kid, array_merge($path, [(string) ($kid['title'] ?? $k)]));
            }
        }
    }

    /**
     * Подписи всех объектов слепка: класс => id => подпись узла
     *
     * @param array $node
     * @param array $ret
     * @return array
     */
    protected static function titles(array $node, array $ret = []): array
    {
        $class = (string) ($node['class'] ?? '');
        $id = static::nodeId($node);

        if ($class !== '' && $id !== null) {
            $ret[$class][$id] = (string) ($node['title'] ?? '');
        }

        foreach ((array) ($node['children'] ?? []) as $kids) {
            foreach ((array) $kids as $kid) {
                $ret = static::titles((array) $kid, $ret);
            }
        }

        return $ret;
    }

    /**
     * Класс строки, на которую ссылается поле через logLinks() модели, иначе null
     *
     * @param string $class
     * @param string $field
     * @return string|null
     */
    protected static function linkTarget(string $class, string $field): ?string
    {
        if (!class_exists($class) || !method_exists($class, 'logLinks') || !method_exists($class, 'logFields')) return null;

        $relation = $class::logFields()[$field]['relation'] ?? null;

        return $relation === null ? null : ($class::logLinks()[$relation] ?? null);
    }

    /**
     * Пары «новый ребёнок → старый ребёнок»
: сначала по id строки, затем по ключу
     * (позиции) среди тех, кому пары ещё не нашлось.
     *
     * Сопоставление по id нужно, чтобы смена порядка не выглядела заменой содержимого:
     * связь variants() сортирует «основной» вариант наверх, и переключение основного
     * меняло позиции — дифф писал полсотни строк вместо одной.
     *
     * @param array|null $old слепок родителя (null — объект только что создан)
     * @param array $old_kids дети старого слепка по ключу
     * @param array $new_kids дети нового слепка по ключу
     * @return array [pairs: ключ нового => узел старого, used: ключ старого => true]
     */
    protected static function pair(?array $old, array $old_kids, array $new_kids): array
    {
        $pairs = [];
        $used = [];

        if ($old === null) return [$pairs, $used];

        $by_id = static::byId($old_kids);

        foreach ($new_kids as $k => $kid) {
            $id = static::nodeId($kid);
            if ($id === null || !isset($by_id[$id])) continue;

            $twin_key = (string) ($by_id[$id]['key'] ?? '');
            if (isset($used[$twin_key])) continue;

            $pairs[$k] = $by_id[$id];
            $used[$twin_key] = true;
        }

        foreach ($new_kids as $k => $kid) {
            $key = (string) $k;
            if (isset($pairs[$k]) || !isset($old_kids[$k]) || isset($used[$key])) continue;

            $pairs[$k] = $old_kids[$k];
            $used[$key] = true;
        }

        return [$pairs, $used];
    }

    /**
     * id строки из слепка (attrs.id): по нему дети сопоставляются в первую очередь
     *
     * @param array $node
     * @return string|null null — id нет (объект пересоздаётся при каждом сохранении)
     */
    protected static function nodeId(array $node): ?string
    {
        $id = $node['attrs']['id'] ?? null;

        return $id === null || $id === '' ? null : (string) $id;
    }

    /**
     * Дети по id строки (те, у кого id есть)
     *
     * @param array $kids
     * @return array
     */
    protected static function byId(array $kids): array
    {
        $ret = [];
        foreach ($kids as $kid) {
            $id = static::nodeId($kid);
            if ($id !== null) $ret[$id] = $kid;
        }

        return $ret;
    }

    /**
     * Поле считает сам портал (итоги, НДС, цены со скидкой) — в ленте такие строки
     * уходят под основные. Помечается 'derived' => true в logFields() модели
     *
     * @param string $class
     * @param string $field
     * @return bool
     */
    protected static function isDerived(string $class, string $field): bool
    {
        if (!class_exists($class) || !method_exists($class, 'logFields')) return false;

        return !empty($class::logFields()[$field]['derived']);
    }

    /**
     * Дети по ключу key
     *
     * @param array $kids
     * @return array
     */
    protected static function keyed(array $kids): array
    {
        $ret = [];
        foreach ($kids as $i => $kid) {
            $ret[(string) ($kid['key'] ?? $i)] = $kid;
        }

        return $ret;
    }

    /**
     * Строка по полю
     *
     * @param string $kind
     * @param string $class
     * @param string $key
     * @param string|null $path
     * @param string $field
     * @param mixed $old
     * @param mixed $new
     * @param string|null $old_label готовая подпись (иначе считается по logFields())
     * @param string|null $new_label
     * @return array
     */
    protected static function row(string $kind, string $class, string $key, ?string $path, string $field, $old, $new, ?string $old_label = null, ?string $new_label = null): array
    {
        return [
            'kind' => $kind,
            'model_class' => $class,
            'model_key' => mb_substr($key, 0, 64),
            'path' => $path === null ? null : mb_substr($path, 0, 500),
            'field' => mb_substr($field, 0, 64),
            'label' => mb_substr(static::fieldLabel($class, $field), 0, 128),
            'derived' => static::isDerived($class, $field),
            'old_value' => static::raw($old),
            'new_value' => static::raw($new),
            'old_label' => $old_label ?? static::label($class, $field, $old),
            'new_label' => $new_label ?? static::label($class, $field, $new),
        ];
    }

    /**
     * Строка по объекту целиком (added / removed)
     *
     * @param string $kind
     * @param array $node
     * @param array $path
     * @return array
     */
    protected static function objectRow(string $kind, array $node, array $path): array
    {
        $class = (string) ($node['class'] ?? '');
        $title = (string) ($node['title'] ?? '');

        return [
            'kind' => $kind,
            'model_class' => $class,
            'model_key' => mb_substr((string) ($node['key'] ?? ''), 0, 64),
            'path' => mb_substr(implode(' → ', $path), 0, 500),
            'field' => null,
            'label' => mb_substr(static::classLabel($class), 0, 128),
            'derived' => false,
            'old_value' => $kind === EntityLogChange::KIND_REMOVED ? $title : null,
            'new_value' => $kind === EntityLogChange::KIND_ADDED ? $title : null,
            'old_label' => $kind === EntityLogChange::KIND_REMOVED ? $title : null,
            'new_label' => $kind === EntityLogChange::KIND_ADDED ? $title : null,
        ];
    }

    /**
     * Нормализованное значение для сравнения
     *
     * @param mixed $value
     * @return string
     */
    public static function normalize($value): string
    {
        if ($value === null || $value === '') return '';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_array($value)) return (string) json_encode($value, JSON_UNESCAPED_UNICODE);

        $text = (string) $value;

        if (is_numeric($text)) {
            if (str_contains($text, '.')) {
                $text = rtrim(rtrim($text, '0'), '.');
            }
            if ($text === '' || $text === '-' || $text === '-0') $text = '0';
        }

        return $text;
    }

    /**
     * Сырое значение для хранения
     *
     * @param mixed $value
     * @return string|null
     */
    public static function raw($value): ?string
    {
        if ($value === null) return null;
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_array($value)) return (string) json_encode($value, JSON_UNESCAPED_UNICODE);

        return (string) $value;
    }

    /**
     * Человеческая подпись значения поля
     *
     * @param string $class
     * @param string $field
     * @param mixed $value
     * @return string
     */
    public static function label(string $class, string $field, $value): string
    {
        try {
            return static::format($class, $field, $value);
        } catch (\Throwable $e) {
            report($e);

            return (string) static::raw($value);
        }
    }

    /**
     * Форматирование по описанию поля (см. HasLogger::logFields())
     *
     * @param string $class
     * @param string $field
     * @param mixed $value
     * @return string
     */
    protected static function format(string $class, string $field, $value): string
    {
        $spec = static::fieldSpec($class, $field);
        $empty = $value === null || $value === '';

        if (!empty($spec['format']) && is_callable($spec['format'])) {
            return $empty ? '—' : (string) $spec['format']($value);
        }

        if (!empty($spec['relation'])) {
            return $empty ? '—' : static::relatedTitle($class, $spec, $value);
        }

        if (!empty($spec['enum'])) {
            if ($empty) return '—';
            $case = $spec['enum']::tryFrom((string) $value);

            return $case ? (string) ($case->data()['label'] ?? $case->value) : (string) $value;
        }

        if (!empty($spec['options'])) {
            return $empty ? '—' : (string) ($spec['options'][(string) $value] ?? $value);
        }

        $type = $spec['type'] ?? static::castType($class, $field);

        if ($type === 'bool') {
            return $empty ? '—' : (filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'да' : 'нет');
        }

        if ($empty) return '—';

        switch ($type) {
            case 'date':
                return Carbon::parse((string) $value)->format('d.m.Y');
            case 'datetime':
                return Carbon::parse((string) $value)->format('d.m.Y H:i');
            case 'money':
                return tools()->cost_normalize((float) $value, '.', false, ' ', false, 2);
            case 'html':
                return Str::limit(static::plain($value), static::LABEL_LIMIT);
            case 'json':
                return Str::limit(is_array($value) ? (string) json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value, static::LABEL_LIMIT);
        }

        return static::normalize($value);
    }

    /**
     * Текст без html-разметки и лишних пробелов
     *
     * @param mixed $value
     * @return string
     */
    public static function plain($value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value))));
    }

    /**
     * Подпись связанной модели: logTitle(), если есть trait, иначе title / name / full_name
     *
     * @param string $class
     * @param array $spec
     * @param mixed $value
     * @return string
     */
    protected static function relatedTitle(string $class, array $spec, $value): string
    {
        $relation = (new $class)->{$spec['relation']}();
        $related = $relation->getRelated();
        $owner = method_exists($relation, 'getOwnerKeyName') ? $relation->getOwnerKeyName() : $related->getKeyName();

        // без глобальных областей: мягко удалённые пользователи тоже находятся
        $row = $related->newQueryWithoutScopes()->where($owner, $value)->first();
        if (empty($row)) return '#' . $value;

        if (!empty($spec['title'])) {
            $title = $row->getAttribute($spec['title']);
            if (is_string($title) && trim($title) !== '') return trim($title);
        }

        if (in_array(HasLogger::class, class_uses_recursive($row), true)) {
            return $row->logTitle();
        }

        foreach (['name', 'full_name', 'title'] as $attr) {
            $title = $row->getAttribute($attr);
            if (is_string($title) && trim($title) !== '') return trim(strip_tags($title));
        }

        return '#' . $value;
    }

    /**
     * Тип поля из $casts модели
     *
     * @param string $class
     * @param string $field
     * @return string|null bool|date|datetime|json|null
     */
    protected static function castType(string $class, string $field): ?string
    {
        $cast = (new $class)->getCasts()[$field] ?? null;
        if (empty($cast)) return null;

        $cast = strtolower((string) $cast);

        if (in_array($cast, ['bool', 'boolean'], true)) return 'bool';
        if (Str::startsWith($cast, ['immutable_datetime', 'datetime', 'timestamp'])) return 'datetime';
        if (Str::startsWith($cast, ['immutable_date', 'date'])) return 'date';
        if (Str::startsWith($cast, ['json', 'array', 'object', 'collection']) || str_contains($cast, 'json')) return 'json';

        return null;
    }

    /**
     * Описание поля из logFields()
     *
     * @param string $class
     * @param string $field
     * @return array
     */
    protected static function fieldSpec(string $class, string $field): array
    {
        return method_exists($class, 'logFields') ? (array) ($class::logFields()[$field] ?? []) : [];
    }

    /**
     * Подпись поля
     *
     * @param string $class
     * @param string $field
     * @return string
     */
    protected static function fieldLabel(string $class, string $field): string
    {
        return method_exists($class, 'logFieldLabel') ? $class::logFieldLabel($field) : $field;
    }

    /**
     * Название типа объекта
     *
     * @param string $class
     * @return string
     */
    protected static function classLabel(string $class): string
    {
        return $class !== '' && method_exists($class, 'logLabel') ? $class::logLabel() : class_basename($class);
    }

    /**
     * Игнорируемые поля класса
     *
     * @param string $class
     * @return array
     */
    protected static function ignore(string $class): array
    {
        return method_exists($class, 'logIgnoreAll') ? $class::logIgnoreAll() : ['id', 'created_at', 'updated_at'];
    }
}
