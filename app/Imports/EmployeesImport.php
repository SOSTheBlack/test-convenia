<?php

namespace App\Imports;

use App\DTO\EmployeeData;
use App\Enums\BrazilianState;
use App\Models\Employee;
use App\Services\Employees\EmployeeService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Illuminate\Support\Str;

class EmployeesImport implements ToCollection, WithHeadingRow, WithChunkReading, WithUpserts, ShouldQueue, SkipsEmptyRows, WithBatchInserts
{
    private EmployeeService $employeeService;

    public function __construct(private int $userId)
    {
        $this->employeeService = app(EmployeeService::class);
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Employee([
            'user_id' => $this->userId, // Usando ID fixo 1 para simplificar
            'name' => $row['name'],
            'email' => $row['email'],
            'document' => $row['document'],
            'city' => $row['city'],
            'state' => BrazilianState::from($row['state']),
            'start_date' => Carbon::parse($row['start_date'])->format('Y-m-d'),
        ]);
    }

    public function collection(Collection $rows)
    {
        $data = $rows->map(fn (Collection $employee) => new EmployeeData(
            user_id: $this->userId,
            name: $employee['name'],
            email: $employee['email'],
            document: preg_replace('/[^0-9]/', '', Str::of($employee['document'])->trim()->numbers()),
            city: $employee['city'],
            state: BrazilianState::from($employee['state'])->value,
            start_date: Carbon::parse($employee['start_date'])->format('Y-m-d'),
        ));

        Log::info('excel collection', $data->toArray());

        $this->employeeService->importFile()->createOrUpdateEmployees($data);

        Log::alert('passou do save');
    }

    /**
     * @return string|array
     */
    public function uniqueBy()
    {
        return ['document'];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2'],
            'email' => ['required', 'email'],
            'document' => ['required', 'string'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string', 'size:2'],
            'start_date' => ['required', 'date_format:d/m/Y'],
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }
}
