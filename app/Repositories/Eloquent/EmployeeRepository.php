<?php

namespace App\Repositories\Eloquent;

use App\DTO\EmployeeData;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function findByDocument(string $document): ?Employee
    {
        return Employee::where('document', $document)->first();
    }

    public function createOrUpdate(EmployeeData $data): Employee
    {
        return Employee::updateOrCreate(
            ['document' => $data->document],
            $data->toArray()
        );
    }

    public function findByUser(int $userId, array $filters = [], int $perPage = 15)
    {
        $query = Employee::where('user_id', $userId);

        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        return $query->paginate($perPage);
    }
}