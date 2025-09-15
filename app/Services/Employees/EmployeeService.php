<?php

namespace App\Services\Employees;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Employees\Resources\ImportCsvResource;

class EmployeeService
{
    public function __construct(private EmployeeRepositoryInterface $employeeRepository)
    {

    }

    public function importFile(): ImportCsvResource
    {
        return app(ImportCsvResource::class);
    }

    public function getEmployeesByUser(int $userId, array $filters = [], int $perPage = 15)
    {
        return $this->employeeRepository->findByUser($userId, $filters, $perPage);
    }
}
