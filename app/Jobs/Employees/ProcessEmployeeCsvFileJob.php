<?php

declare(strict_types=1);

namespace App\Jobs\Employees;

use App\Services\Contracts\CsvProcessingServiceInterface;
use App\Services\Contracts\FileUploadServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class ProcessEmployeeCsvFileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600; // 10 minutes for large files
    public int $tries = 3;
    public int $maxExceptions = 3;

    /**
     * @var array<int>
     */
    public array $backoff = [10, 30, 60]; // Progressive backoff in seconds

    public function __construct(
        private readonly string $filePath,
        private readonly int $userId
    ) {
    }

    public function handle(
        CsvProcessingServiceInterface $csvProcessingService,
        FileUploadServiceInterface $fileUploadService,
        LoggerInterface $logger
    ): void {
        $logger->info('Starting CSV import job', [
            'user_id' => $this->userId,
            'file_path' => $this->filePath,
            'attempt' => $this->attempts()
        ]);

        try {
            if (!Storage::exists($this->filePath)) {
                throw new RuntimeException('File not found: ' . $this->filePath);
            }

            $fileSize = Storage::size($this->filePath);
            $logger->info('Processing CSV file', [
                'file_size' => $fileSize,
                'file_path' => $this->filePath
            ]);

            $startTime = microtime(true);
            $csvProcessingService->processCsvFile($this->filePath, $this->userId);
            $endTime = microtime(true);

            $logger->info('CSV import job completed successfully', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'file_size' => $fileSize,
                'processing_time' => round($endTime - $startTime, 2) . 's'
            ]);
        } catch (Throwable $exception) {
            $logger->error('CSV import job failed', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'error' => $exception->getMessage(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries
            ]);

            // Only clean up file if this is the last attempt
            if ($this->attempts() >= $this->tries) {
                $fileUploadService->deleteFile($this->filePath);
            }

            throw $exception;
        }

        // Clean up file on success
        $fileUploadService->deleteFile($this->filePath);
    }

    public function failed(Throwable $exception): void
    {
        app(LoggerInterface::class)->error('CSV import job permanently failed', [
            'user_id' => $this->userId,
            'file_path' => $this->filePath,
            'error' => $exception->getMessage()
        ]);
    }
}
