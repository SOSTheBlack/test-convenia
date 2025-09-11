<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\CsvImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            // Create a temporary UploadedFile from the stored file
            $fullPath = Storage::path($this->filePath);
            $file = new UploadedFile(
                $fullPath,
                basename($this->filePath),
                'text/csv',
                null,
                true
            );

            $results = $csvImportService->processCsvFile($file, $this->userId);

            Log::info('CSV import job completed', [
                'job_id' => $this->jobId,
                'results' => $results
            ]);

            // Clean up the temporary file
            Storage::delete($this->filePath);

        } catch (\Exception $e) {
            Log::error('CSV import job failed', [
                'job_id' => $this->jobId,
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);

            // Clean up the temporary file even on failure
            Storage::delete($this->filePath);

            throw $e;
        }
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