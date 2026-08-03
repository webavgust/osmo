<?php

namespace App\Modules\Pub\OrderTask\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateStep2Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can_do('order_task_edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'point' => 'array|required',
            'service' => 'array|nullable'
        ];
    }
}
