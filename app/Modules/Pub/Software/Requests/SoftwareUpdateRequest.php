<?php

namespace App\Modules\Pub\Software\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SoftwareUpdateRequest extends FormRequest
{
    /**
     * Проверка на доступ пользователя
     *
     * @return bool
     */
    protected $messages = [
        'validation.unique' => '11',
    ];

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
            'user' => 'required|exists:users,id',
            'course' => 'required|exists:courses,id',
            'cost_internal_inner' => 'nullable',
            'cost_internal_outer' => 'nullable',
            'cost_distance' => 'nullable',
            'cost_web' => 'nullable'
        ];
    }
}
