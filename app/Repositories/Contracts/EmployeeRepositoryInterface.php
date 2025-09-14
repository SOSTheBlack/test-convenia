<?php

namespace App\Repositories\Contracts;

use App\DTO\EmployeeData;
use App\Models\Employee;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{
    public function findByDocument(string $document): ?Employee;

    public function createOrUpdate(EmployeeData $data): Employee;

    public function create(EmployeeData $data): Employee;

    public function createOrUpdateMany(Collection $data): void;

    public function findByUser(int $userId, array $filters = [], int $perPage = 15);
}
