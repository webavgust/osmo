<?php

namespace App\Modules\Pub\EntityLog\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Слепок агрегата и его гидрация (patch v29).
 *
 * Формат узла: {class, key, title, attrs, children: {relation: [узлы]}}.
 * attrs — все сырые атрибуты модели (getAttributes(): даты и json строками,
 * как в базе), чтобы гидрация была точной; технические поля (logIgnore())
 * отбрасываются уже при сравнении, в EntityLogDiff. key — logKey($index):
 * id либо позиция в связи. Дети читаются заново из базы, не из кэша связи.
 */
class EntityLogSnapshot
{
    /**
     * Слепок модели с детьми по logChildren()
     *
     * @param Model $model
     * @param int|null $index позиция среди детей родителя (с нуля); null — корень
     * @return array
     */
    public static function make(Model $model, ?int $index = null): array
    {
        $node = [
            'class' => get_class($model),
            'key' => $index === null ? (string) $model->getKey() : (string) $model->logKey($index),
            'title' => mb_substr((string) $model->logTitle($index === null ? null : $index + 1), 0, 255),
            'attrs' => $model->getAttributes(),
            'children' => [],
        ];

        foreach ($model::logChildren() as $relation => $class) {
            $node['children'][$relation] = [];

            foreach (static::children($model, $relation) as $i => $child) {
                $node['children'][$relation][] = static::make($child, $i);
            }
        }

        return $node;
    }

    /**
     * Дети по связи — свежим запросом, в порядке связи (без orderBy — по id)
     *
     * @param Model $model
     * @param string $relation
     * @return Collection
     */
    public static function children(Model $model, string $relation): Collection
    {
        $query = $model->{$relation}();

        if (empty($query->getQuery()->getQuery()->orders)) {
            $query->orderBy($query->getRelated()->getQualifiedKeyName());
        }

        return $query->get();
    }

    /**
     * Модель из слепка: exists = true, атрибуты как в базе на тот момент,
     * дети — загруженные связи (setRelation) рекурсивно. belongsTo-связи
     * (партнёр, валюта, сценарий) подгружаются живыми по FK.
     *
     * @param array $data
     * @return Model
     */
    public static function hydrate(array $data): Model
    {
        $index = [];
        $model = static::build($data, $index);

        static::link($model, $index);

        return $model;
    }

    /**
     * Модель из узла слепка; в $index собираются все модели дерева: класс => id => модель
     *
     * @param array $data
     * @param array $index
     * @return Model
     */
    protected static function build(array $data, array &$index): Model
    {
        $class = $data['class'];
        $prototype = new $class;

        $model = $prototype->newInstance([], true);
        $model->setRawAttributes((array) ($data['attrs'] ?? []), true);
        $model->exists = true;

        $id = $model->getKey();
        if ($id !== null && $id !== '') $index[$class][(string) $id] = $model;

        foreach ((array) ($data['children'] ?? []) as $relation => $rows) {
            // циклом, а не array_map(fn): стрелочная функция захватила бы $index копией,
            // и дети не попали бы в индекс
            $items = [];
            foreach (array_values((array) $rows) as $row) {
                $items[] = static::build($row, $index);
            }

            $collection = !empty($items) ? $items[0]->newCollection($items) : new Collection();

            $model->setRelation($relation, $collection);
        }

        return $model;
    }

    /**
     * Проставить связи из logLinks() по собранному индексу: строка, на которую ссылается
     * ребёнок, берётся из того же слепка. Иначе связь грузилась бы живой и у удалённой
     * строки возвращала null — страница состояния на дату падала
     *
     * @param Model $model
     * @param array $index
     * @return void
     */
    protected static function link(Model $model, array $index): void
    {
        foreach ($model::logLinks() as $relation => $class) {
            if (!method_exists($model, $relation)) continue;

            $key = $model->{$relation}()->getForeignKeyName();
            $value = $model->getAttribute($key);
            $found = $value === null ? null : ($index[$class][(string) $value] ?? null);

            if ($found !== null) $model->setRelation($relation, $found);
        }

        foreach ($model->getRelations() as $relation => $value) {
            if ($value instanceof Collection) {
                foreach ($value as $child) static::link($child, $index);
            }
        }
    }

    /**
     * JSON слепка для хранения
     *
     * @param array $data
     * @return string
     */
    public static function encode(array $data): string
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
