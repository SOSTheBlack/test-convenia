<?php

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeImportRequest;
use App\Imports\EmployeesImport;
use App\Jobs\ProcessEmployeeCsvFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class UploadEmployeesController extends Controller
{
    /**
     * Upload de funcionários
     *
     * @param  EmployeeImportRequest  $request
     * @return JsonResponse
     */
    public function __invoke(EmployeeImportRequest $request): JsonResponse
    {
        try {
            $file = $request->file('employees');
            $user = $request->user();
            $jobId = Str::uuid()->toString();

            $fileName = 'temp_csv_employee_' . $jobId . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('temp',  $fileName);

            // Dispatch job for asynchronous processing
            ProcessEmployeeCsvFile::dispatch($filePath, $user->id, $jobId);

            return response()->json([
                'message' => 'Arquivo enviado com sucesso e será processado em breve',
                'job_id' => $jobId
            ], Response::HTTP_ACCEPTED);

        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Erro ao processar arquivo',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
