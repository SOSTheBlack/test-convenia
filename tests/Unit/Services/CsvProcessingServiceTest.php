<?php

namespace Tests\Unit\Services;

use App\Imports\EmployeesImport;
use App\Services\Csv\CsvProcessingService;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class CsvProcessingServiceTest extends TestCase
{
    private CsvProcessingService $service;
    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->mock(LoggerInterface::class);
        $this->service = new CsvProcessingService($this->logger);
    }

    /** @test */
    public function it_processes_csv_file_successfully()
    {
        // Arrange
        $filePath = 'imports/test.csv';
        $userId = 1;

        // Create a real file temporarily for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test');
        file_put_contents($tempFile, "name,email,document,city,state,start_date\nJohn,john@test.com,123456789,SP,SP,2023-01-01");

        Storage::shouldReceive('path')
            ->once()
            ->with($filePath)
            ->andReturn($tempFile);

        Excel::shouldReceive('import')
            ->once()
            ->withArgs(function ($import, $path) use ($tempFile) {
                return $import instanceof EmployeesImport && $path === $tempFile;
            });

        // Setup logger expectations
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Starting CSV file processing', [
                'file_path' => $filePath,
                'user_id' => $userId
            ]);

        $this->logger->shouldReceive('info')
            ->once()
            ->with('CSV file processing completed', [
                'file_path' => $filePath,
                'user_id' => $userId
            ]);

        // Act
        $this->service->processCsvFile($filePath, $userId);

        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        // Assert - This test verifies behavior through mocks
        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_exception_when_file_not_found()
    {
        // Arrange
        $filePath = 'imports/nonexistent.csv';
        $userId = 1;
        $fullPath = '/fake/path/imports/nonexistent.csv';

        Storage::shouldReceive('path')
            ->once()
            ->with($filePath)
            ->andReturn($fullPath);

        // Setup logger expectations
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Starting CSV file processing', [
                'file_path' => $filePath,
                'user_id' => $userId
            ]);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("File not found: {$filePath}");

        $this->service->processCsvFile($filePath, $userId);
    }

    /** @test */
    public function it_logs_processing_start_and_completion()
    {
        // Arrange
        $filePath = 'imports/test.csv';
        $userId = 1;

        // Create a real file temporarily for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test');
        file_put_contents($tempFile, "name,email,document,city,state,start_date\nJohn,john@test.com,123456789,SP,SP,2023-01-01");

        Storage::shouldReceive('path')
            ->once()
            ->with($filePath)
            ->andReturn($tempFile);

        Excel::shouldReceive('import')
            ->once();

        // Setup logger expectations - more specific matching
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Starting CSV file processing', [
                'file_path' => $filePath,
                'user_id' => $userId
            ]);

        $this->logger->shouldReceive('info')
            ->once()
            ->with('CSV file processing completed', [
                'file_path' => $filePath,
                'user_id' => $userId
            ]);

        // Act
        $this->service->processCsvFile($filePath, $userId);

        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        // Assert - Verify that both log messages were called
        $this->assertTrue(true);
    }

    /** @test */
    public function it_uses_correct_import_class()
    {
        // Arrange
        $filePath = 'imports/test.csv';
        $userId = 1;

        // Create a real file temporarily for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test');
        file_put_contents($tempFile, "name,email,document,city,state,start_date\nJohn,john@test.com,123456789,SP,SP,2023-01-01");

        Storage::shouldReceive('path')
            ->once()
            ->with($filePath)
            ->andReturn($tempFile);

        // Setup logger expectations
        $this->logger->shouldReceive('info')->twice();

        // Verify that Excel::import is called with EmployeesImport
        Excel::shouldReceive('import')
            ->once()
            ->withArgs(function ($import, $path) use ($tempFile, $userId) {
                return $import instanceof EmployeesImport
                    && $path === $tempFile;
            });

        // Act
        $this->service->processCsvFile($filePath, $userId);

        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        // Assert - Verify the correct import class is used
        $this->assertTrue(true);
    }
}
