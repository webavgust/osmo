<?php

namespace App\Modules\Pub\Constant\Models;

use App\Models\ModuleModel;

/**
 * Константы портала (таблица consts).
 *
 * Код константы хранится в колонке `key` (в интерфейсе админ-панели — «Код»).
 *
 * Бизнес-настройки читаются типизированно: value() / int() / float() / json().
 * Каждый такой метод принимает значение по умолчанию из кода — если строки нет
 * или значение пустое, код работает как раньше. Таблица читается одним запросом
 * и кэшируется на запрос (в статическом массиве); кэш сбрасывают set(),
 * сохранение и удаление модели (админ-панель) и flush().
 *
 * В долгоживущем процессе (очередь, демон) кэш живёт до flush() — для таких
 * мест значения стоит перечитывать явно.
 */
class Constant extends ModuleModel
{
    public $timestamps = false;
    protected $table = 'consts';

    protected $fillable = ['name', 'key', 'value', 'note', 'system'];

    /** Кэш на запрос: код → значение (null — таблица ещё не читалась) */
    protected static ?array $values = null;

    protected static function booted()
    {
        // правка из админ-панели должна сразу действовать в этом же запросе
        static::saved(fn() => static::flush());
        static::deleted(fn() => static::flush());
    }

    /**
     * сеттер для константы
     *
     * @param $key ключ
     * @param $value значение
     * @return void
     */
    public static function set($key, $value)
    {
        $count = \DB::selectOne('SELECT COUNT(*) as value FROM consts WHERE `key` = ?', [$key]);
        if ($count->value == 1) {
            \DB::update('UPDATE consts SET value = ? WHERE `key` = ?', [$value, $key]);
        } else {
            // name NOT NULL без значения по умолчанию: подставляем код, название поправят в админ-панели
            \DB::update('INSERT INTO consts SET value = ?, `key` = ?, name = ?', [$value, $key, $key]);
        }

        static::flush();
    }

    /**
     * геттер для константы
     *
     * @param string $key
     * @return string|integer|null
     */
    public static function get(string $key)
    {
        $ret = \DB::selectOne('SELECT value FROM consts WHERE `key` = ? ', [$key]);
        if (empty($ret->value))
            return null;

        return $ret->value;
    }

    /**
     * Строковое значение константы с кэшем на запрос.
     *
     * Строки нет или значение пустое (после trim) — возвращается $default.
     *
     * @param string $code Код константы (колонка key)
     * @param mixed $default Значение по умолчанию из кода
     * @return mixed
     */
    public static function value(string $code, $default = null)
    {
        $value = static::all_values()[$code] ?? null;

        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        return (string) $value;
    }

    /**
     * Целое значение константы. Не число — $default.
     *
     * @param string $code
     * @param int|null $default
     * @return int|null
     */
    public static function int(string $code, ?int $default = null): ?int
    {
        $value = static::number($code);

        return $value === null ? $default : (int) round($value);
    }

    /**
     * Дробное значение константы (запятая как разделитель тоже понимается). Не число — $default.
     *
     * @param string $code
     * @param float|null $default
     * @return float|null
     */
    public static function float(string $code, ?float $default = null): ?float
    {
        $value = static::number($code);

        return $value === null ? $default : $value;
    }

    /**
     * Значение-массив из JSON (объект или массив). Не JSON — $default.
     *
     * @param string $code
     * @param mixed $default
     * @return mixed массив либо $default
     */
    public static function json(string $code, $default = null)
    {
        $value = static::value($code);
        if ($value === null) return $default;

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * Сбросить кэш значений на запрос
     *
     * @return void
     */
    public static function flush(): void
    {
        static::$values = null;
    }

    /**
     * Все значения таблицы одним запросом: код → значение
     *
     * @return array
     */
    protected static function all_values(): array
    {
        if (static::$values === null) {
            static::$values = \DB::table('consts')->pluck('value', 'key')->all();
        }

        return static::$values;
    }

    /**
     * Числовое значение константы или null
     *
     * @param string $code
     * @return float|null
     */
    protected static function number(string $code): ?float
    {
        $value = static::value($code);
        if ($value === null) return null;

        $value = str_replace([' ', ','], ['', '.'], trim($value));

        return is_numeric($value) ? (float) $value : null;
    }
}
