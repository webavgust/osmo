<?php

namespace App\Modules\Pub\Reminder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'hide' => 'nullable|boolean',
            'title' => 'required|string',
            'message' => 'nullable|string',
            'time' => 'required|array',
            'user'=> 'nullable|array',
            'module' => 'nullable|array'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
