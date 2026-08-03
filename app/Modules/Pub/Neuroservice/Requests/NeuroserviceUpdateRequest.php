<?php

namespace App\Modules\Pub\Neuroservice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NeuroserviceUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Проверка на существование указанного класса
     *
     * @param $validator
     * @return void
     */

    /**
     * Правила валидации
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:3|max:128',
            'tech_name' => 'nullable|string|min:0|max:100',
            'cost' => 'required|array',
            'cost.month' => 'nullable|int|min:0',
            'cost.year' => 'nullable|int|min:0',
            'cost.unlimited' => 'nullable|int|min:0',
            'cb_registered' => 'nullable|bool',
            'sort' => 'required|integer'
        ];
    }
}
