<?php

namespace App\Modules\Pub\AccessGroup\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessGroupUpdateRequest extends FormRequest
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
            'prefix' => [
                'required',
                'string',
                'min:1',
                'max:128',
                Rule::unique('access_groups')->ignore($this->group->id, 'id')
            ],
            'sort' => 'required|integer'
        ];
    }
}
