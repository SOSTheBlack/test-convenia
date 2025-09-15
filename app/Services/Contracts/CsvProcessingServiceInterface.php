<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface CsvProcessingServiceInterface
{
    public function processCsvFile(string $filePath, int $userId): void;
}
