<?php

declare(strict_types=1);

namespace App\Services\Employees;

use App\DTO\EmployeeData;
use App\Events\EmployeeCreated;
use App\Events\EmployeeUpdated;
use App\Exceptions\CsvImportException;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Contracts\EmployeeServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Throwable;

class EmployeeService implements EmployeeServiceInterface
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createOrUpdateEmployee(EmployeeData $data): ?Employee
    {
        $this->logger->info("Service creating or updating employee with document {$data->document}");

        try {
            $employee = $this->employeeRepository->findByDocument($data->document);
            $previousEmployee = clone $employee;
            $employee->fill($data->toModelArray());

            if (!$employee->isDirty()) {
                throw new CsvImportException("Employee with document {$data->document} has no changes.");
            }

            $this->logger->info("DEBUG {$data->document}", [
                'current' => $employee->toArray(),
                'new' => $previousEmployee->toArray()
            ]);

            $employee->saveOrFail();
            $this->logger->info("Service updated employee with document {$data->document}", [
                'employee' => $employee->toArray(),
                'previous' => $previousEmployee->toArray()
            ]);

            $this->dispatchUpdatedEvent($employee, $previousEmployee);

            return $employee;
        } catch (ModelNotFoundException) {
            $employee = $this->employeeRepository->create($data);
            $this->logger->info("Created new employee {$data->document}", ['employee' => $employee->toArray()]);

            $this->dispatchCreatedEvent($employee);

            return $employee;
        } catch (CsvImportException) {
            return null;
        } catch (Throwable $exception) {
            $this->logger->error("Could not create/update {$data->document}", ['error' => $exception->getMessage()]);
            return null;
        }
    }

    public function createOrUpdateMany(Collection $data): void
    {
        $this->employeeRepository->createOrUpdateMany($data);
    }

    public function getEmployeesByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->employeeRepository->findByUser($userId, $filters, $perPage);
    }

    public function findByDocument(string $document): ?Employee
    {
        try {
            return $this->employeeRepository->findByDocument($document);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    public function getEmployeesToNotify(?int $userId = null): Collection
    {
        try {
            return $this->employeeRepository->findToNotify($userId);
        } catch (ModelNotFoundException) {
            return new Collection();
        }
    }

    public function markNotificationsSent(array $employeeIds): void
    {
        $this->employeeRepository->updateNotificationStatus($employeeIds, false);
    }

    private function dispatchCreatedEvent(Employee $employee): void
    {
        event(new EmployeeCreated($employee));
    }

    private function dispatchUpdatedEvent(Employee $employee, Employee $previousEmployee): void
    {
        /** @var \App\Models\User $user */
        $user = $employee->user;

        if ($user) {
            event(new EmployeeUpdated(
                $user,
                EmployeeData::fromModel($employee),
                EmployeeData::fromModel($previousEmployee)
            ));
        }
    }
}
