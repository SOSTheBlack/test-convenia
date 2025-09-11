<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization is handled by auth:api middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employees' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240', // 10MB max
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'employees.required' => 'O arquivo CSV é obrigatório.',
            'employees.file' => 'O arquivo deve ser um arquivo válido.',
            'employees.mimes' => 'O arquivo deve estar no formato CSV.',
            'employees.max' => 'O arquivo não pode ser maior que 10MB.',
        ];
    }
}