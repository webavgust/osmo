<?php

namespace App\Modules\Pub\Proposal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProposalRequest extends FormRequest
{
    /**
     * Проверка на доступ пользователя
     *
     * @return bool
     */
    public function authorize()
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
            'parent' => 'nullable|integer|exists:proposals,id',
            'name' => 'required|string',
            'name_alt' => 'nullable|string',
            'date' => 'required|date',
            'number' => 'required|string',
            'manager' => 'required|exists:users,id',
            'company' => 'required|exists:companies,id',
            'partner' => 'required|exists:partners,id',
            'nds' => 'required|numeric|min:0|max:100',

            'cost_source' => 'nullable|string|in:save,current',
            'period_main' => 'required|int|min:1|max:1000',
            'variant_id' => 'nullable|array',
            'variant_id.*' => 'nullable|numeric|min:0',
            'period_active' => 'required|array',
            'period_active.*' => 'bool',
            'partner_platform_discount' => 'nullable',
            'partner_platform_discount.*' => 'nullable|int|min:0|max:100',
            'partner_neuro_discount' => 'nullable',
            'partner_neuro_discount.*' => 'nullable|int|min:0|max:100',
            'partner_soft_discount' => 'nullable',
            'partner_soft_discount.*' => 'nullable|int|min:0|max:100',

            'period' => 'required|array',
            'period.*' => 'string|in:pilot,year,unlimited',
            'period_value' => 'required|array',
            'period_value.*' => 'nullable|numeric',

            'scenario' => 'nullable|array',
            'scenario.*.cb_process' => 'nullable|bool',
            'scenario.*.scenario' => 'nullable|exists:scenarios,id',
            'scenario.*.real_name' => 'nullable|string',
            'scenario.*.real_sync' => 'nullable|bool',
            'scenario.*.mnemonic' => 'nullable|string',
            'scenario.*.comment' => 'nullable|string',
            'scenario.*.sort' => 'nullable|int|min:0',

            'cell' => 'nullable|array',
            'cell.*.*.count' => 'nullable|numeric|min:0',
            'cell.*.*.discount' => 'nullable|numeric|min:0|max:100',
            'cell.*.*.nds' => 'nullable|bool',

            'cost' => 'nullable|array',
            'cost.*.*' => 'required|numeric|min:0',

            'soft' => 'nullable|array',
            'soft.*.cb_process' => 'nullable|bool',
            'soft.*.extended' => 'nullable|string',
            'soft.*.notice' => 'nullable|string',
            'soft.*.sort' => 'nullable|int|min:0',

            'work' => 'nullable|array',
            'work.*.cb_process' => 'nullable|bool',
            'work.*.extended' => 'nullable|string',
            'work.*.notice' => 'nullable|string',
            'work.*.group' => 'nullable|string',
            'work.*.sort' => 'nullable|int|min:0',

            'soft_cell' => 'nullable|required_if:work,array',
            'soft_cell.*.*.count' => 'nullable|numeric|min:0',
            'soft_cell.*.*.cost' => 'nullable|numeric|min:0',
            'soft_cell.*.*.partner' => 'nullable|bool',
            'soft_cell.*.*.nds' => 'nullable|bool',

            'work_cell' => 'nullable|required_if:work,array',
            'work_cell.*.*.count' => 'nullable|numeric|min:0',
            'work_cell.*.*.cost' => 'nullable|numeric|min:0',
            'work_cell.*.*.discount' => 'nullable|numeric|min:0',
            'work_cell.*.*.discount_partner' => 'nullable|numeric|min:0',
            'work_cell.*.*.nds' => 'nullable|bool',


            'platform_cost' => 'nullable|array',
            'platform_cost.*.*' => 'required|numeric|min:0',

            'platform_cell' => 'nullable|array',
            'platform_cell.*.*.count' => 'nullable|numeric|min:0',
            'platform_cell.*.*.discount' => 'nullable|numeric|min:0|max:100',

            'new_iteration' => 'nullable|bool',
            'neuroForceCost' => 'nullable|string',
        ];
    }
}
