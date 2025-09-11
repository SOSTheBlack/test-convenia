<?php

namespace App\Repositories\Contracts;

use App\DTO\EmployeeData;
use App\Models\Employee;

interface EmployeeRepositoryInterface
{
    public function findByDocument(string $document): ?Employee;
    
    public function createOrUpdate(EmployeeData $data): Employee;
    
    public function findByUser(int $userId, array $filters = [], int $perPage = 15);
}