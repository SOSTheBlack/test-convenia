<?php

namespace App\Http\Controllers\API\Employees;

use App\Http\Controllers\API\ApiController;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;

class ShowEmployeeController extends ApiController
{

    public function __invoke(Employee $employee): JsonResponse
    {
        $this->authorize('employee', $employee);

        return response()->json(
            $employee
        );
    }
}
