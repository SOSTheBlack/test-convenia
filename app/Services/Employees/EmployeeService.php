<?php

namespace App\Services\Employees;

use App\DTO\EmployeeData;
use App\Events\EmployeeCreated;
use App\Events\EmployeeUpdated;
use App\Imports\EmployeesImport;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Employees\Resources\ImportCsvResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

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
