<?php

namespace App\Services;

use App\Imports\EmployeesImport;
use App\Services\Employees\EmployeeService;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CsvImportService
{
    public function __construct(private EmployeeService $employeeService)
    {

    }

    public function processCsvFile(string $filePath, int $userId): void
    {
        Excel::import(new EmployeesImport($userId), Storage::path($filePath));
    }
}
