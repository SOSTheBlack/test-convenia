<?php

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\EmployeeImportRequest;
use App\Services\Employees\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

use function Illuminate\Log\log;

final class UploadEmployeesController extends Controller
{
    public function __construct(private EmployeeService $employeeService)
    {

    }

    /**
     * Upload de funcionários
     *
     * @param  EmployeeImportRequest  $request
     * @return JsonResponse
     */
    public function __invoke(EmployeeImportRequest $request): JsonResponse
    {
        try {
            $filePath = $this->employeeService->importFile()->uploadFromRequest($request);
            $this->employeeService->importFile()->dispatchJob($filePath, $request->user()->id);

            log()->info('File uploaded and job dispatched', [
                'user_id' => $request->user()->id,
                'file_path' => $filePath
            ]);

            return response()->json(['message' => 'Arquivo enviado com sucesso e será processado em breve'], Response::HTTP_OK);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Erro ao processar arquivo',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
