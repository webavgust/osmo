<?php

namespace App\Modules\Pub\OrderTask\Requests;

use App\Modules\Pub\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class EditRequest extends FormRequest
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
    public function rules(Request $request)
    {
        return [
            'contacts' => 'nullable|string',
            'object' => 'required|array',
            'address' => 'required|array',
            'point' => 'required|array'
        ];
    }
}
