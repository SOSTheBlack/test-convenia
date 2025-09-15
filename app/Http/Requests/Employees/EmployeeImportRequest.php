<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use App\Imports\EmployeesImport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeImportRequest extends FormRequest
{
    private const MAX_FILE_SIZE = 10240; // 10MB in KB
    private const ALLOWED_MIMES = 'csv,txt';
    private const FIELD_NAME = 'employees';

    public function authorize(): bool
    {
        return true; // Authorization is handled by auth:api middleware
    }

    /**
     * @return array<string, array<string|int>>
     */
    public function rules(): array
    {
        return [
            self::FIELD_NAME => [
                'required',
                'file',
                'mimes:' . self::ALLOWED_MIMES,
                'max:' . self::MAX_FILE_SIZE,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            self::FIELD_NAME . '.required' => 'O arquivo CSV é obrigatório.',
            self::FIELD_NAME . '.file' => 'O arquivo deve ser um arquivo válido.',
            self::FIELD_NAME . '.mimes' => 'O arquivo deve estar no formato CSV.',
            self::FIELD_NAME . '.max' => 'O arquivo não pode ser maior que 10MB.',
        ];
    }

    /**
     * @param \Illuminate\Validation\Validator $validator
     * @throws ValidationException
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $file = $this->file(self::FIELD_NAME);

            if (!$file) {
                return;
            }

            try {
                $this->validateCsvContent($file, $validator);
            } catch (\Exception $e) {
                $validator->errors()->add(self::FIELD_NAME, 'Erro ao processar arquivo CSV: ' . $e->getMessage());
            }
        });
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param \Illuminate\Validation\Validator $validator
     * @throws ValidationException
     */
    private function validateCsvContent($file, $validator): void
    {
        $employeeImport = new EmployeesImport($this->user()->id);
        $collection = Excel::toCollection($employeeImport, $file);

        if ($collection->isEmpty() || $collection->first()->isEmpty()) {
            $validator->errors()->add(self::FIELD_NAME, 'O arquivo CSV está vazio ou não contém dados válidos.');
            return;
        }

        $rows = $collection->first();
        $hasErrors = false;

        foreach ($rows as $index => $row) {
            $rowValidator = Validator::make($row->toArray(), $employeeImport->rules());

            if ($rowValidator->fails()) {
                $hasErrors = true;
                $validator->errors()->add(
                    'linha_' . ($index + 2), // +2 because Excel rows start at 1 and we have a header
                    'Linha ' . ($index + 2) . ' contém erros: ' . $rowValidator->errors()->first()
                );
            }
        }

        if ($hasErrors) {
            throw new ValidationException($validator, response()->json([
                'message' => 'Dados inválidos encontrados no arquivo CSV',
                'errors' => $validator->errors()
            ], Response::HTTP_NOT_ACCEPTABLE));
        }
    }
}
