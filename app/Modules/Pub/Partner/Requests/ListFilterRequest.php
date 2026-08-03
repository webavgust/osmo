<?php

namespace App\Modules\Pub\Partner\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListFilterRequest extends FormRequest
{
    /**
     * Проверка на доступ пользователя
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Правила валидации
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => 'nullable|string',
            'grade' => 'nullable|string',
        ];
    }
}
