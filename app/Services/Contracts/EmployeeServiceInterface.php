<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\DTO\EmployeeData;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EmployeeServiceInterface
{
    public function createOrUpdateEmployee(EmployeeData $data): ?Employee;

    public function createOrUpdateMany(Collection $data): void;

    /**
     * @param array<string, mixed> $filters
     */
    public function getEmployeesByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findByDocument(string $document): ?Employee;

    public function getEmployeesToNotify(?int $userId = null): Collection;

    /**
     * @param array<int> $employeeIds
     */
    public function markNotificationsSent(array $employeeIds): void;
}
