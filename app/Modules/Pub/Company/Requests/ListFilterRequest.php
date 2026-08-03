<?php

namespace App\Modules\Pub\Company\Requests;

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
            'partner' => 'nullable|exists:partners,id',
            'sector' => 'nullable|exists:sectors,id',
        ];
    }
}
