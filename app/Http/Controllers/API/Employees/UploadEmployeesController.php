<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\EmployeeImportRequest;
use App\Services\Contracts\FileUploadServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use Throwable;

final class UploadEmployeesController extends Controller
{
    private const FIELD_NAME = 'employees';

    public function __construct(
        private readonly FileUploadServiceInterface $fileUploadService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(EmployeeImportRequest $request): JsonResponse
    {
        try {
            $filePath = $this->fileUploadService->uploadFromRequest($request, self::FIELD_NAME);
            $this->fileUploadService->dispatchProcessingJob($filePath, $request->user()->id);

            $this->logger->info('File uploaded and job dispatched', [
                'user_id' => $request->user()->id,
                'file_path' => $filePath
            ]);

            return response()->json([
                'message' => 'Arquivo enviado com sucesso e será processado em breve'
            ], Response::HTTP_OK);
        } catch (Throwable $exception) {
            $this->logger->error('Error processing file upload', [
                'error' => $exception->getMessage(),
                'user_id' => $request->user()->id ?? null
            ]);

            return response()->json([
                'message' => 'Erro ao processar arquivo',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
