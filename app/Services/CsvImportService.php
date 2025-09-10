<?php

namespace App\Services;

use App\DTO\EmployeeData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CsvImportService
{
    public function __construct(
        private EmployeeService $employeeService
    ) {
    }

    public function processCsvFile(UploadedFile $file, int $userId): array
    {
        $results = [
            'total_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'errors' => []
        ];

        try {
            $handle = fopen($file->getPathname(), 'r');
            
            if ($handle === false) {
                throw new \Exception('Unable to open CSV file');
            }

            // Read and validate header
            $header = fgetcsv($handle);
            $expectedColumns = ['name', 'email', 'document', 'city', 'state', 'start_date'];
            
            if (!$this->validateHeader($header, $expectedColumns)) {
                throw new \Exception('Invalid CSV header. Expected columns: ' . implode(', ', $expectedColumns));
            }

            $rowNumber = 1; // Start at 1 for data rows (header is row 0)

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $results['total_records']++;

                try {
                    $data = array_combine($header, $row);
                    $employeeData = EmployeeData::fromArray($data, $userId);
                    
                    $validationErrors = $employeeData->validate();
                    if (!empty($validationErrors)) {
                        $results['failed_records']++;
                        $results['errors'][] = [
                            'row' => $rowNumber,
                            'errors' => $validationErrors
                        ];
                        continue;
                    }

                    $this->employeeService->createOrUpdateEmployee($employeeData);
                    $results['successful_records']++;

                } catch (\Exception $e) {
                    $results['failed_records']++;
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'errors' => ['general' => $e->getMessage()]
                    ];
                    
                    Log::error('Error processing CSV row', [
                        'row' => $rowNumber,
                        'error' => $e->getMessage(),
                        'data' => $row ?? null
                    ]);
                }
            }

            fclose($handle);

        } catch (\Exception $e) {
            Log::error('Error processing CSV file', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            
            throw $e;
        }

        return $results;
    }

    private function validateHeader(array $header, array $expectedColumns): bool
    {
        return count(array_intersect($header, $expectedColumns)) === count($expectedColumns);
    }
}