<?php

namespace App\Modules\Pub\Access\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Проверка на существование указанного класса
     *
     * @param $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = collect($this->request->all())->only(['class', 'method'])->toArray();
            if (!class_exists($data['class'])) {
                $validator->errors()->add('class_method', 'Класс не существует!');
            } elseif (!method_exists($data['class'], $data['method'])) {
                $validator->errors()->add('class_method', 'Метод не существует!');
            }
        });
    }

    /**
     * Правила валидации
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:3|max:128',
            'code' => [
                'required',
                'string',
                'min:3',
                'max:128',
                Rule::unique('accesses')->ignore($this->access->id, 'id')
            ],
            'class' => 'required|string',
            'method' => 'required|string',
            'sort' => 'required|integer'
        ];
    }
}
