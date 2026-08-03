<?php

namespace App\Modules\Pub\Proposal\Requests;

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
            'company' => 'nullable|exists:companies,id',
            'scenario' => 'nullable|exists:scenarios,id',
            'neuroservice' => 'nullable|exists:neuroservices,id',
            'sended_at' => 'nullable|string',
            'cost_from' => 'nullable|int|min:0',
            'cost_to' => 'nullable|int|min:0',
            'hasEmptyScenario' => 'nullable|bool',
        ];
    }
}
