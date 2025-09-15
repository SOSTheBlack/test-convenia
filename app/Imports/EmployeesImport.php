<?php

namespace App\Imports;

use App\DTO\EmployeeData;
use App\Enums\BrazilianState;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
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
        // Mapeia os dados brutos do CSV para objetos EmployeeData
        $employeeDataCollection = $rows->map(fn (Collection $employee) => new EmployeeData(
            user_id: $this->userId,
            name: $employee['name'],
            email: $employee['email'],
            document: Str::of($employee['document'])->trim()->numbers(),
            city: $employee['city'],
            state: BrazilianState::from($employee['state'])->value,
            start_date: Carbon::parse($employee['start_date'])->format('Y-m-d')
        ));

        // Filtra apenas os registros que precisam ser criados ou atualizados
        $dataToUpsert = collect();

        foreach ($employeeDataCollection as $employeeData) {
            try {
                // Tenta encontrar o funcionário pelo documento
                /** @var Employee|null $existingEmployee */
                $existingEmployee = app(EmployeeRepositoryInterface::class)->findByDocument($employeeData->document);

                if ($existingEmployee) {
                    // Cria uma cópia para preservar o estado original
                    $originalEmployee = clone $existingEmployee;

                    $employDataToFill = $employeeData->toArray();
                    unset($employDataToFill['send_notification']);

                    // Preenche o modelo com os novos dados
                    $existingEmployee->fill($employeeData->toArray());

                    Log::info('===========', ['isDirty' => $existingEmployee->isDirty(), 'changes' => $existingEmployee->getDirty(), 'original' => $originalEmployee->toArray(), 'new' => $existingEmployee->toArray()]);

                    // Verifica se houve alguma mudança
                    if ($existingEmployee->isDirty()) {
                        $existingEmployee->send_notification = true;
                        $dataToUpsert->push(EmployeeData::fromModel($existingEmployee));

                        Log::info("Dados modificados para documento {$existingEmployee->document}", ['changed_fields' => $existingEmployee->getDirty()]);
                    }
                }
            } catch (\Exception $e) {
                // Em caso de erro, adiciona o registro completo para tentativa de criação
                Log::error('Erro ao processar funcionário: ' . $e->getMessage(), [
                    'document' => $employeeData->document
                ]);
                $employeeData->setSendNotification(true);
                $dataToUpsert->push($employeeData);
            }
        }

        Log::info('Registros para upsert', ['count' => $dataToUpsert->count()]);

        // Se houver dados para upsert, processa
        if ($dataToUpsert->isNotEmpty()) {
            $this->employeeService->importFile()->createOrUpdateEmployees($dataToUpsert);
            Log::alert('Processamento de upsert finalizado com sucesso');
        } else {
            Log::info('Nenhum registro precisa ser atualizado ou criado');
        }
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
