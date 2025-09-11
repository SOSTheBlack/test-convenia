<?php

namespace App\Services;

use App\DTO\EmployeeData;
use App\Events\EmployeeUpdated;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeService
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository
    ) {
    }

    public function createOrUpdateEmployee(EmployeeData $data): Employee
    {
        $existingEmployee = $this->employeeRepository->findByDocument($data->document);
        $isUpdate = $existingEmployee !== null;

        $employee = $this->employeeRepository->createOrUpdate($data);

        if ($isUpdate) {
            event(new EmployeeUpdated($employee, $existingEmployee));
        }

        return $employee;
    }

    public function getEmployeesByUser(int $userId, array $filters = [], int $perPage = 15)
    {
        return $this->employeeRepository->findByUser($userId, $filters, $perPage);
    }
}