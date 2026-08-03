<?php

namespace App\Modules\Pub\Calendar\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class EditEventRequest extends FormRequest
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
            'caption' => 'required|string',
            'text' => 'string|nullable',
            'color' => 'required|string|in:success,danger,warning,primary,secondary',
            'mode' => 'required|in:day,dates,time,future',
            'date' => 'required_if:mode,day|exclude_unless:mode,day',
            'dates' => 'required_if:mode,dates|exclude_unless:mode,dates',
            'datetime' => 'required_if:mode,time|exclude_unless:mode,time|array',
        ];
    }
}
