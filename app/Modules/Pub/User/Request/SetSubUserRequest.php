<?php

namespace App\Modules\Pub\User\Request;

use Illuminate\Foundation\Http\FormRequest;

class SetSubUserRequest extends FormRequest
{
    /**
     * Проверка на доступ пользователя
     *
     * @return bool
     */
    public function authorize()
    {
        return _can('users_sub_users_control');
    }

    /**
     * Правила валидации
     *
     * @return array
     */
    public function rules()
    {
        return [
            //'user' => 'required|array|nullable',
        ];
    }
}
