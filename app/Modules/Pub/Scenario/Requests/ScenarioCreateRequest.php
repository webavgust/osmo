<?php

namespace App\Modules\Pub\Scenario\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;

class ScenarioCreateRequest extends FormRequest
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
            'work_scenario' => 'nullable|string',
            'event_reminder' => 'nullable|string',
            'work_result' => 'nullable|string',
            'force_cost' => 'nullable|array',
            'cost_force.year' => 'nullable|int|min:0',
            'cost_force.unlimited' => 'nullable|int|min:0',
            'sort' => 'required|integer',
            'neuro' => 'nullable|array',
            'neuro.*' => 'nullable|exists:neuroservices,id',
            'cost_rules' => 'required|array',
        ];
    }
}
