<?php

namespace App\Jobs;

use App\Imports\EmployeesImport;
use App\Services\CsvImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessEmployeeCsvFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function __construct(
        private string $filePath,
        private int $userId,
        private string $jobId
    ) {
    }

    public function handle(CsvImportService $csvImportService): void
    {
        Log::info('Starting CSV import job', [
            'job_id' => $this->jobId,
            'user_id' => $this->userId,
            'file_path' => $this->filePath
        ]);

        try {
            if (!Storage::exists($this->filePath)) {
                throw new \RuntimeException('File not found: ' . $this->filePath);
            }

            $csvImportService->processCsvFile($this->filePath, $this->userId);
        } catch (\Throwable $exception) {
            Log::error('CSV import job failed', [
                'job_id' => $this->jobId,
                'user_id' => $this->userId,
                'error' => $exception->getMessage()
            ]);

            Storage::delete($this->filePath);

            throw $exception;
        }

        // try {
        //     // Create a temporary UploadedFile from the stored file
        //     $fullPath = Storage::path($this->filePath);
        //     $file = new UploadedFile(
        //         $fullPath,
        //         basename($this->filePath),
        //         'text/csv',
        //         null,
        //         true
        //     );

        //     $results = $csvImportService->processCsvFile($file, $this->userId);

        //     Log::info('CSV import job completed', [
        //         'job_id' => $this->jobId,
        //         'results' => $results
        //     ]);

        //     // Clean up the temporary file
        //     Storage::delete($this->filePath);

        // } catch (\Throwable $exception) {
        //     Log::error('CSV import job failed', [
        //         'job_id' => $this->jobId,
        //         'user_id' => $this->userId,
        //         'error' => $exception->getMessage()
        //     ]);

        //     // Clean up the temporary file even on failure
        //     Storage::delete($this->filePath);

        //     throw $exception;
        // }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CSV import job permanently failed', [
            'job_id' => $this->jobId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage()
        ]);

        // Clean up the temporary file
        Storage::delete($this->filePath);
    }
}
