<?php

namespace App\Jobs\Employees;

use App\Services\Employees\EmployeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessEmployeeCsvFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 1;

    public function __construct(private string $filePath, private int $userId)
    {
    }

    public function handle(EmployeeService $employeeService): void
    {
        Log::info('Starting CSV import job', [
            'user_id' => $this->userId,
            'file_path' => $this->filePath
        ]);

        try {
            if (!Storage::exists($this->filePath)) {
                throw new RuntimeException('File not found: ' . $this->filePath);
            }

            $employeeService->importFile()->processCsvFile($this->filePath, $this->userId);
        } catch (Throwable $exception) {
            $this->failed($exception);

            throw $exception;
        } finally {
            Storage::delete($this->filePath);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('CSV import job permanently failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage()
        ]);
    }
}
