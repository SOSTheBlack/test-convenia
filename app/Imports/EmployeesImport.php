<?php

declare(strict_types=1);

namespace App\Imports;

use App\DTO\EmployeeData;
use App\Enums\BrazilianState;
use App\Rules\ValidDate;
use App\Services\Contracts\EmployeeServiceInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Psr\Log\LoggerInterface;

class EmployeesImport implements ToCollection, WithHeadingRow, WithChunkReading, WithUpserts, ShouldQueue, SkipsEmptyRows, WithBatchInserts
{
    private EmployeeServiceInterface $employeeService;
    private LoggerInterface $logger;

    public function __construct(private readonly int $userId)
    {
        $this->employeeService = app(EmployeeServiceInterface::class);
        $this->logger = app(LoggerInterface::class);
    }

    public function collection(Collection $rows): void
    {
        $employeeDataCollection = $rows->map(function (Collection $employee) {
            return new EmployeeData(
                userId: $this->userId,
                name: $employee['name'],
                email: $employee['email'],
                document: Str::of($employee['document'])->trim()->numbers()->toString(),
                city: $employee['city'],
                state: BrazilianState::from($employee['state'])->value,
                startDate: Carbon::parse($employee['start_date'])->format('Y-m-d'),
                sendNotification: true
            );
        });

        $dataToProcess = collect();

        foreach ($employeeDataCollection as $employeeData) {
            try {
                $existingEmployee = $this->employeeService->findByDocument($employeeData->document);

                if ($existingEmployee) {
                    $originalData = EmployeeData::fromModel($existingEmployee);

                    // Verifica se houve mudanças
                    if ($this->hasChanges($originalData, $employeeData)) {
                        $dataToProcess->push($employeeData->withSendNotification(true));
                        $this->logger->info("Data changed for document {$employeeData->document}");
                    } else {
                        $this->logger->info("No changes for document {$employeeData->document}");
                    }
                } else {
                    $dataToProcess->push($employeeData);
                    $this->logger->info("New employee with document {$employeeData->document}");
                }
            } catch (Exception $e) {
                $this->logger->error('Error processing employee: ' . $e->getMessage(), [
                    'document' => $employeeData->document
                ]);
                $dataToProcess->push($employeeData);
            }
        }

        $this->logger->info('Records to process', ['count' => $dataToProcess->count()]);

        if ($dataToProcess->isNotEmpty()) {
            foreach ($dataToProcess as $employeeData) {
                $this->employeeService->createOrUpdateEmployee($employeeData);
            }
            $this->logger->info('Processing completed successfully');
        } else {
            $this->logger->info('No records need to be updated or created');
        }
    }

    private function hasChanges(EmployeeData $original, EmployeeData $new): bool
    {
        return $original->name !== $new->name ||
               $original->email !== $new->email ||
               $original->city !== $new->city ||
               $original->state !== $new->state ||
               $original->startDate !== $new->startDate;
    }

    /**
     * @return string|array<string>
     */
    public function uniqueBy(): string|array
    {
        return ['document'];
    }

    /**
     * @return array<string, array<int, string|\App\Rules\ValidDate>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2'],
            'email' => ['required', 'email'],
            'document' => ['required', 'numeric'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string', 'size:2'],
            'start_date' => ['required', new ValidDate('Y-m-d')],
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
