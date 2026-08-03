<?php

namespace App\Modules\Pub\Reminder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditTimeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'time' => 'nullable|array',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
