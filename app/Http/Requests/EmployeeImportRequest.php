<?php

namespace App\Http\Requests;

use App\Imports\EmployeesImport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Response;

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

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $file = $this->file('employees');

            if (!$file) {
                return;
            }

            $employeeImportExcel = new EmployeesImport($this->user()->id);

            $collection = Excel::toCollection($employeeImportExcel, $file);

            if ($collection->isEmpty() || $collection->first()->isEmpty()) {
                return;
            }

            foreach ($collection->first() as $key => $row) {
                $validatorExcel = Validator::make($row->toArray(), $employeeImportExcel->rules());

                if ($validatorExcel->fails()) {
                    $validator->errors()->add($row->get('name'), ['errors' => $validatorExcel->errors(), 'row' => $row->toArray()]);
                }
            }

            if ($validator->errors()->isEmpty()) {
                return;
            }

            throw new \Illuminate\Validation\ValidationException($validator, response()->json([
                'message' => 'Invalid Data in File(csv)',
                'errors' => $validator->errors()
            ], Response::HTTP_NOT_ACCEPTABLE));
        });
    }
}
