<?php

namespace App\Modules\Pub\UserNote\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'favorite' => 'nullable|boolean',
            'title' => 'required|string',
            'text' => 'nullable|string'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
