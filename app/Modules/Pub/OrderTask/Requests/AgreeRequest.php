<?php

namespace App\Modules\Pub\OrderTask\Requests;

use App\Modules\Pub\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class   AgreeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can_do('order_task_agree');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request)
    {
        return [
            'user' => 'required|array',
        ];
    }
}
