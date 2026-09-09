<?php

namespace App\Modules\Pub\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListFilterRequest extends FormRequest
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
            'contract_conclusion' => 'nullable|exclude_unless:cb_contract_conclusion,1',
            'order_sent_to_techdep' => 'nullable|exclude_unless:cb_order_sent_to_techdep,1',
            'md_specify_finaldate' => 'nullable|exclude_unless:cb_md_specify_finaldate,1',
            'cb_md_specify_finaldate' => 'nullable|exclude_if:md_specify_finaldate,',
            'cb_contract_conclusion' => 'nullable|exclude_if:contract_conclusion,',
            'cb_order_sent_to_techdep' => 'nullable|exclude_if:order_sent_to_techdep,',
            'author' => 'nullable',
            'manager' => 'nullable',
            'curator' => 'nullable',
            'order_id' => 'nullable|string',
            'order_name' => 'nullable|string',
            'status' => 'array|nullable',
            'is_archived' => 'bool|nullable',
        ];
    }
}
