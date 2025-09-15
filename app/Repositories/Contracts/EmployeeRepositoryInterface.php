<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTO\EmployeeData;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{
    public const int NOTIFICATION_DAYS = 1;

    /**
     * @throws ModelNotFoundException
     */
    public function findByDocument(string $document): Employee;

    public function create(EmployeeData $data): Employee;

    public function createOrUpdateMany(Collection $data): void;

    /**
     * @param array<string, mixed> $filters
     */
    public function findByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * @throws ModelNotFoundException
     */
    public function findToNotify(?int $userId = null, int $days = self::NOTIFICATION_DAYS): Collection;

    /**
     * @param array<int> $employeeIds
     */
    public function updateNotificationStatus(array $employeeIds, bool $status): void;
}
