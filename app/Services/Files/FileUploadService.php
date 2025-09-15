<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Jobs\Employees\ProcessEmployeeCsvFileJob;
use App\Services\Contracts\FileUploadServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService implements FileUploadServiceInterface
{
    private const TEMP_PREFIX = 'temp_csv_employee_';
    private const TEMP_DIR = 'temp';

    public function uploadFromRequest(Request $request, string $fieldName): string
    {
        $file = $request->file($fieldName);

        if (!$file) {
            throw new \InvalidArgumentException("No file found with field name: {$fieldName}");
        }

        $fileName = sprintf(
            '%s%s.%s',
            self::TEMP_PREFIX,
            Str::uuid(),
            $file->getClientOriginalExtension()
        );

        return $file->storeAs(self::TEMP_DIR, $fileName);
    }

    public function dispatchProcessingJob(string $filePath, int $userId): void
    {
        ProcessEmployeeCsvFileJob::dispatch($filePath, $userId);
    }

    public function deleteFile(string $filePath): void
    {
        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
        }
    }
}
