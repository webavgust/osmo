<?php

namespace App\Modules\Pub\Access\Requests;

use App\Modules\Pub\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class SetAccessDepartmentRequest extends FormRequest
{
    public function authorize(Request $request)
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
            'access' => 'required|array'
        ];
    }
}
