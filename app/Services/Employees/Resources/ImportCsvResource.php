<?php

namespace App\Services\Employees\Resources;

use App\DTO\EmployeeData;
use App\DTO\UserData;
use App\Events\EmployeeCreated;
use App\Events\EmployeeUpdated;
use App\Exceptions\CsvImportException;
use App\Imports\EmployeesImport;
use App\Jobs\Employees\ProcessEmployeeCsvFileJob;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Employees\EmployeeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Throwable;

class ImportCsvResource
{
    public const FIELD_NAME = 'employees';
    public const TEMP_PREFIX = 'temp_csv_employee_';
    public const TEMP_DIR = 'temp';

    public function __construct(private EmployeeService $employeeService, private EmployeeRepositoryInterface $employeeRepository)
    {

    }

    public function uploadFromRequest(Request $request): string
    {
        $file = $request->file(self::FIELD_NAME);
        $fileName = vsprintf('%s.%s', [self::TEMP_PREFIX . Str::uuid(), $file->getClientOriginalExtension()]);

        return  $request->file(self::FIELD_NAME)->storeAs(self::TEMP_DIR,  $fileName);
    }

    public function dispatchJob(string $filePath, int $userId): void
    {
        ProcessEmployeeCsvFileJob::dispatch($filePath, $userId);
    }

    public function processCsvFile(string $filePath, int $userId): void
    {
        Log::info('processCsvFile');
        Excel::import(new EmployeesImport($userId), Storage::path($filePath));
    }

    public function createOrUpdateEmployees(Collection $data): ?Employee
    {
        return $this->employeeRepository->createOrUpdateMany($data);
    }

    public function createOrUpdateEmployee(EmployeeData $data): ?Employee
    {
        Log::info("Service creating or updating employee with document {$data->document}");

        try {
            $employee = $this->employeeRepository->findByDocument($data->document);
            $previousEmployee = clone $employee;
            $employee->fill($data->toArray());

            if (! $employee->isDirty()) {
                $previousEmployee = false;
                throw new CsvImportException("Employee with document {$data->document} has no changes." );
            }

            Log::info("DEBUG {$data->document}", ['current' => $employee->toArray(), 'new' => $previousEmployee->toArray()]);
            $employee->saveOrFail();
            Log::info("----------Service updated employee with document {$data->document}", ['employee' => $employee->toArray(), 'previous' => $previousEmployee->toArray()]);
        } catch (ModelNotFoundException $exception) {
            $previousEmployee = null;
            $employee = $this->employeeRepository->create($data);
            Log::info("Criado novo funcionário {$data->document}", ['employee' => $employee->toArray()]);
        } catch (CsvImportException $csvImportException) {
            $employee = null;
        } catch (Throwable $exception) {
            Log::error("Não foi possível criar/atualizar {$data->document}", ['error' => $exception->getMessage()]);
        } finally {
            if ($previousEmployee !== false) {
                Log::info("Note updating or creating employee with document {$data->document}", ['employee' => $employee->toArray(), 'previous' => $previousEmployee ? $previousEmployee->toArray() : null]);
                $this->eventDispatch($employee, $previousEmployee);
            }

            return $employee ?? null;
        }
    }

    private function eventDispatch(Employee $employee, ?Employee $previousEmployee): void
    {
        if (! $previousEmployee) {
            event(new EmployeeCreated($employee));
            return;
        }

        event(new EmployeeUpdated($employee->user, EmployeeData::fromModel($employee), $previousEmployee ? EmployeeData::fromModel($previousEmployee) : null));
        return;
    }
}
