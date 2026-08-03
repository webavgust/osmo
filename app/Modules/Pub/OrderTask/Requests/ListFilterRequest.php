<?php

namespace App\Modules\Pub\OrderTask\Requests;

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
            'created_at' => 'nullable|exclude_unless:cb_created_at,1',
            'cb_created_at' => 'nullable|exclude_if:created_at,',

            'plan_visit_at' => 'nullable|exclude_unless:cb_plan_visit_at,1',
            'cb_plan_visit_at' => 'nullable|exclude_if:plan_visit_at,',

            'fact_visit_at' => 'nullable|exclude_unless:cb_fact_visit_at,1',
            'cb_fact_visit_at' => 'nullable|exclude_if:fact_visit_at,',

            'dp_annex_date' => 'nullable|exclude_unless:cb_dp_annex_date,1',
            'cb_dp_annex_date' => 'nullable|exclude_if:dp_annex_date,',

            'client_name' => 'nullable|exclude_unless:cb_client_name,1',
            'cb_client_name' => 'nullable|exclude_if:client_name,',

            'contract_name' => 'nullable|exclude_unless:cb_contract_name,1',
            'cb_contract_name' => 'nullable|exclude_if:contract_name,',

            'status' => 'nullable|array',
            'creator' => 'nullable|array',
        ];
    }
}
