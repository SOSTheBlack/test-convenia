<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListEmployeesController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService
    ) {
    }

    /**
     * Lista de funcionários paginada
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->only(['department']);
        $perPage = $request->get('per_page', 15);

        $employees = $this->employeeService->getEmployeesByUser(
            $user->id,
            $filters,
            $perPage
        );

        return response()->json($employees, Response::HTTP_OK);
    }
}
