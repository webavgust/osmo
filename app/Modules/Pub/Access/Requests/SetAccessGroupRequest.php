<?php

namespace App\Modules\Pub\Access\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetAccessGroupRequest extends FormRequest
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
            'access' => 'required|array'
        ];
    }
}
