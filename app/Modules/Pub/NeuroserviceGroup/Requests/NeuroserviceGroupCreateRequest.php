<?php

namespace App\Modules\Pub\NeuroserviceGroup\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NeuroserviceGroupCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // TODO: Переделать на проверку прав доступа пользователя
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
            'name' => 'required|string|min:3|max:128',
            'sort' => 'required|integer'
        ];
    }
}
