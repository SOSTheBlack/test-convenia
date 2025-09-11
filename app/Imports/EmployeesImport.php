<?php

namespace App\Imports;

use App\Enums\BrazilianState;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class EmployeesImport implements ToModel, WithHeadingRow, WithUpserts, WithChunkReading, ShouldQueue
{
    public function __construct(private int $userId)
    {

    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        Log::info('Importing employee', ['row' => $row]);
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

    /**
     * @return string|array
     */
    public function uniqueBy()
    {
        return ['document'];
    }

    public function chunkSize(): int
    {
        return 1000;
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
}
