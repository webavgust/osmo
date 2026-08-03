<?php

namespace App\Modules\Pub\Access\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;

class AccessCreateRequest extends FormRequest
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
            'code' => 'required|string|unique:accesses|min:3|max:128',
            'class' => 'required|string',
            'method' => 'required|string',
            'sort' => 'required|integer'
        ];
    }
}
