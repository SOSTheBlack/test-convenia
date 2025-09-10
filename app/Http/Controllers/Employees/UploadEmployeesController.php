<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeImportRequest;
use App\Jobs\ProcessEmployeeCsvFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            
            // Generate unique job ID
            $jobId = Str::uuid()->toString();
            
            // Store file temporarily with unique name
            $fileName = 'temp_csv_' . $jobId . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('temp', $fileName);
            
            // Dispatch job for asynchronous processing
            ProcessEmployeeCsvFile::dispatch($filePath, $user->id, $jobId);
            
            return response()->json([
                'message' => 'Arquivo enviado com sucesso e será processado em breve',
                'job_id' => $jobId
            ], Response::HTTP_ACCEPTED);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar arquivo',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
