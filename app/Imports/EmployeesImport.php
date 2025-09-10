<?php

namespace App\Imports;

use App\Models\Employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class EmployeesImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Employee([
            'user_id' => 1, // Usando ID fixo 1 para simplificar
            'name' => $row['name'],
            'email' => $row['email'],
            'document' => $row['document'],
            'city' => $row['city'],
            'state' => $row['state'],
            'start_date' => Carbon::parse($row['start_date'])->format('Y-m-d'),
        ]);
    }
}
