<?php

namespace App\Modules\Pub\Calendar\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class SetEventRequest extends FormRequest
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
    public function rules(Request $request)
    {
        return [
            'data.allDay' => 'required|boolean',
            'data.set_date' => 'required_without_all:data.start,data.end|date',
            'data.start' => 'required_without:data.set_date',
            'data.end' => 'required_without:data.set_date',
        ];
    }
}
