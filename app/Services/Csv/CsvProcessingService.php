<?php

declare(strict_types=1);

namespace App\Services\Csv;

use App\Imports\EmployeesImport;
use App\Services\Contracts\CsvProcessingServiceInterface;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Psr\Log\LoggerInterface;

class CsvProcessingService implements CsvProcessingServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function processCsvFile(string $filePath, int $userId): void
    {
        $this->logger->info('Starting CSV file processing', [
            'file_path' => $filePath,
            'user_id' => $userId
        ]);

        $fullPath = Storage::path($filePath);

        if (!file_exists($fullPath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        Excel::import(new EmployeesImport($userId), $fullPath);

        $this->logger->info('CSV file processing completed', [
            'file_path' => $filePath,
            'user_id' => $userId
        ]);
    }
}
