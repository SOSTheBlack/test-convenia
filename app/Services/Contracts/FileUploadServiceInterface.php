<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use Illuminate\Http\Request;

interface FileUploadServiceInterface
{
    public function uploadFromRequest(Request $request, string $fieldName): string;

    public function dispatchProcessingJob(string $filePath, int $userId): void;

    public function deleteFile(string $filePath): void;
}
