<?php

namespace App\Repositories\Contracts;

use App\DTO\EmployeeData;
use App\Models\Employee;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{
    public const int NOTIFICATION_DAYS = 1;

    public function findByDocument(string $document): ?Employee;

    public function createOrUpdate(EmployeeData $data): Employee;

    public function create(EmployeeData $data): Employee;

    public function createOrUpdateMany(Collection $data): void;

    public function findByUser(int $userId, array $filters = [], int $perPage = 15);

    public function findToNotify(?int $userId = null, int $days = self::NOTIFICATION_DAYS): Collection;

    public function updateNotificationStatus(array $employeeIds, bool $status): void;
}
