<?php

namespace App\Modules\Pub\Access\Requests;

use App\Modules\Pub\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class SetAccessUserRequest extends FormRequest
{
    /**
     * Проверка на доступ пользователя
     *
     * @return bool
     */
    public function authorize(Request $request, User $user)
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
