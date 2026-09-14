<?php

namespace App\Modules\Admin\Consts\Services;

use App\Modules\Pub\Constant\Models\Constant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Константы портала в админ-панели (patch v28, этап C).
 *
 * Код константы (`consts.key`) задаётся при создании и дальше не меняется —
 * по нему константу читает код портала. У системных констант (`system = 1`)
 * значение пишет сам код (например, время синхронизации с Битрикс24), поэтому
 * здесь меняются только название и примечание, а удалить их нельзя.
 *
 * Значение-JSON (объект или массив) проверяется при сохранении и хранится
 * компактно, в одну строку. Кэш значений (Constant::value() и т.п.) сбрасывает
 * сама модель при сохранении и удалении.
 */
class AdminConstService
{
    /** Флаги json_encode для компактного хранения JSON */
    protected const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * Параметры отбора списка из запроса
     *
     * @param array $input
     * @return array
     */
    public static function params(array $input): array
    {
        return [
            'q' => trim((string) ($input['q'] ?? '')),
        ];
    }

    /**
     * Список констант: каждое слово поиска должно найтись в коде, названии или примечании
     *
     * @param array $params результат params()
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function list(array $params)
    {
        $builder = Constant::query();

        if ($params['q'] !== '') {
            foreach (preg_split('/\s+/u', $params['q']) as $word) {
                $builder->where(function ($query) use ($word) {
                    foreach (['key', 'name', 'note'] as $field) {
                        $query->orWhere($field, 'LIKE', '%' . $word . '%');
                    }
                });
            }
        }

        return $builder->orderBy('key')->get();
    }

    /**
     * Значение — JSON-объект или JSON-массив
     *
     * @param string|null $value
     * @return bool
     */
    public static function isJson(?string $value): bool
    {
        $value = trim((string) $value);

        if ($value === '' || !in_array($value[0], ['{', '['], true)) {
            return false;
        }

        $decoded = json_decode($value);

        return json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded));
    }

    /**
     * Значение для поля формы: JSON — с отступами, остальное как есть
     *
     * @param string|null $value
     * @return string
     */
    public static function pretty(?string $value): string
    {
        if (!static::isJson($value)) {
            return (string) $value;
        }

        return json_encode(json_decode((string) $value), static::JSON_FLAGS | JSON_PRETTY_PRINT);
    }

    /**
     * Создать константу
     *
     * @param array $input key, name, value, note
     * @return Constant
     * @throws ValidationException
     */
    public static function create(array $input): Constant
    {
        $data = static::validate($input, null);

        $constant = new Constant();
        $constant->key = $data['key'];
        $constant->name = $data['name'];
        $constant->value = $data['value'];
        $constant->note = $data['note'];
        $constant->system = 0;
        $constant->save();

        return $constant;
    }

    /**
     * Изменить константу. Код не меняется; у системной не меняется и значение.
     *
     * @param Constant $constant
     * @param array $input name, value, note
     * @return Constant
     * @throws ValidationException
     */
    public static function update(Constant $constant, array $input): Constant
    {
        $data = static::validate($input, $constant);

        $constant->name = $data['name'];
        $constant->note = $data['note'];

        if (!$constant->system) {
            $constant->value = $data['value'];
        }

        $constant->save();

        return $constant;
    }

    /**
     * Удалить несистемную константу.
     *
     * Код, который её читает, вернётся к значению по умолчанию из кода.
     *
     * @param Constant $constant
     * @return void
     * @throws ValidationException
     */
    public static function delete(Constant $constant): void
    {
        if ($constant->system) {
            throw ValidationException::withMessages([
                'key' => 'Системную константу удалить нельзя: её значение пишет код портала.',
            ]);
        }

        $constant->delete();
    }

    /**
     * Проверить поля формы и привести значение к виду для хранения
     *
     * @param array $input
     * @param Constant|null $constant null — создание
     * @return array ['key', 'name', 'value', 'note']
     * @throws ValidationException
     */
    protected static function validate(array $input, ?Constant $constant): array
    {
        $data = [
            'key' => $constant ? $constant->key : trim((string) ($input['key'] ?? '')),
            'name' => trim((string) ($input['name'] ?? '')),
            'value' => trim((string) ($input['value'] ?? '')),
            'note' => trim((string) ($input['note'] ?? '')),
        ];

        $rules = [
            'name' => 'required|string|max:128',
            'value' => 'nullable|string|max:65000',
            'note' => 'nullable|string|max:2000',
        ];

        if (!$constant) {
            $rules['key'] = ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9_]+$/'];
        }

        $validator = Validator::make($data, $rules, [
            'required' => 'Заполните поле «:attribute».',
            'max' => 'Поле «:attribute» — не длиннее :max символов.',
            'string' => 'Поле «:attribute» заполнено неверно.',
            'regex' => 'Код — только латиница, цифры и знак подчёркивания, без пробелов.',
        ], [
            'key' => 'Код',
            'name' => 'Название',
            'value' => 'Значение',
            'note' => 'Примечание',
        ]);

        // значение системной константы пишет код — его из формы не проверяем и не сохраняем
        $check_value = !$constant || !$constant->system;

        // JSON-режим: текущее значение — JSON, либо новое похоже на JSON
        $json = $check_value && $data['value'] !== ''
            && (($constant && static::isJson($constant->value)) || in_array($data['value'][0], ['{', '['], true));

        $validator->after(function ($validator) use ($data, $constant, $json) {
            if (!$constant && $data['key'] !== '' && Constant::where('key', $data['key'])->exists()) {
                $validator->errors()->add('key', 'Константа с кодом «' . $data['key'] . '» уже есть.');
            }

            if ($json) {
                $decoded = json_decode($data['value']);

                if (json_last_error() !== JSON_ERROR_NONE || !(is_array($decoded) || is_object($decoded))) {
                    $validator->errors()->add('value', 'Значение должно быть корректным JSON (объект или массив): '
                        . (json_last_error() !== JSON_ERROR_NONE ? json_last_error_msg() : 'получилось простое значение') . '.');
                }
            }
        });

        $validator->validate();

        if ($json) {
            $data['value'] = json_encode(json_decode($data['value']), static::JSON_FLAGS);
        }

        $data['value'] = $data['value'] !== '' ? $data['value'] : null;
        $data['note'] = $data['note'] !== '' ? $data['note'] : null;

        return $data;
    }
}
