<?php

namespace App\Modules\Pub\UserSettings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sidebar_mode' => 'nullable'
        ];
    }
}
