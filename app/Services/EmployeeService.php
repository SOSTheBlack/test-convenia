<?php

namespace App\Services;

use App\DTO\EmployeeData;
use App\Events\EmployeeCreated;
use App\Events\EmployeeUpdated;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Log;

class EmployeeService
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository
    ) {
    }

    public function createOrUpdateEmployee(EmployeeData $data): Employee
    {
        Log::info("Service creating or updating employee with document {$data->document}");
        $employee = $this->employeeRepository->createOrUpdate($data);
        $existingEmployee = $this->employeeRepository->findByDocument($data->document);

        $this->eventDispatch($employee, $existingEmployee);

        return $employee;
    }

    public function getEmployeesByUser(int $userId, array $filters = [], int $perPage = 15)
    {
        return $this->employeeRepository->findByUser($userId, $filters, $perPage);
    }

    private function eventDispatch(Employee $employee, ?Employee $previousEmployee): void
    {
        if (! $previousEmployee) {
            event(new EmployeeCreated($employee));
            return;
        }

        event(new EmployeeUpdated($employee, $previousEmployee));
        return;
    }
}
