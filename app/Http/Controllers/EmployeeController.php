<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{

    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('employee', $employee);

        return response()->json(
            $employee
        );
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('employee', $employee);

        return response()->json([
            'Success' => $employee->delete()
        ]);
    }
}
