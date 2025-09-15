<?php

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\API\ApiController;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;

class DestroyEmployeeController extends ApiController
{
    public function __invoke(Employee $employee): JsonResponse
    {
        $this->authorize('employee', $employee);

        try {
            return response()->json([
                'success' => $employee->delete()
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => true,
                'message' => 'Erro ao excluir funcionário.',
                'details' => $exception->getMessage()
            ], 500);
        }
    }
}
