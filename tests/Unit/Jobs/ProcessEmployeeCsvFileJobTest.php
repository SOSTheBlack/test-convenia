<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Employees\ProcessEmployeeCsvFileJob;
use App\Services\Contracts\CsvProcessingServiceInterface;
use App\Services\Contracts\FileUploadServiceInterface;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class ProcessEmployeeCsvFileJobTest extends TestCase
{
    private CsvProcessingServiceInterface&MockInterface $csvProcessingService;
    private FileUploadServiceInterface&MockInterface $fileUploadService;
    private LoggerInterface&MockInterface $logger;
    private ProcessEmployeeCsvFileJob $job;
    private string $filePath;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csvProcessingService = $this->mock(CsvProcessingServiceInterface::class);
        $this->fileUploadService = $this->mock(FileUploadServiceInterface::class);
        $this->logger = $this->mock(LoggerInterface::class);

        $this->filePath = 'imports/test.csv';
        $this->userId = 1;
        $this->job = new ProcessEmployeeCsvFileJob($this->filePath, $this->userId);
    }

    /** @test */
    public function it_processes_csv_file_successfully()
    {
        // Arrange
        Storage::shouldReceive('exists')
            ->once()
            ->with($this->filePath)
            ->andReturn(true);

        Storage::shouldReceive('size')
            ->once()
            ->with($this->filePath)
            ->andReturn(1024);

        $this->logger->shouldReceive('info')
            ->once()
            ->with('Starting CSV import job', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'attempt' => 1
            ]);

        $this->logger->shouldReceive('info')
            ->once()
            ->with('Processing CSV file', [
                'file_size' => 1024,
                'file_path' => $this->filePath
            ]);

        $this->csvProcessingService->shouldReceive('processCsvFile')
            ->once()
            ->with($this->filePath, $this->userId);

        $this->logger->shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'CSV import job completed successfully' &&
                       $context['user_id'] === $this->userId &&
                       $context['file_path'] === $this->filePath &&
                       $context['file_size'] === 1024 &&
                       isset($context['processing_time']);
            });

        $this->fileUploadService->shouldReceive('deleteFile')
            ->once()
            ->with($this->filePath);

        // Act
        $this->job->handle(
            $this->csvProcessingService,
            $this->fileUploadService,
            $this->logger
        );

        // Assert - Verification is done through mocks
        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_exception_when_file_not_found()
    {
        // Arrange
        Storage::shouldReceive('exists')
            ->once()
            ->with($this->filePath)
            ->andReturn(false);

        $this->logger->shouldReceive('info')
            ->once()
            ->with('Starting CSV import job', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'attempt' => 1
            ]);

        $this->logger->shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'CSV import job failed' &&
                       $context['user_id'] === $this->userId &&
                       $context['file_path'] === $this->filePath &&
                       str_contains($context['error'], 'File not found') &&
                       $context['attempt'] === 1;
            });

        // File should be deleted on final attempt (which is attempt 1 when tries = 3)
        // Since attempts() returns 1 and tries is 3, condition attempts() >= tries is false
        $this->fileUploadService->shouldNotReceive('deleteFile');

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found: ' . $this->filePath);

        $this->job->handle(
            $this->csvProcessingService,
            $this->fileUploadService,
            $this->logger
        );
    }

    /** @test */
    public function it_handles_processing_failure_on_first_attempt()
    {
        // Arrange
        Storage::shouldReceive('exists')
            ->once()
            ->with($this->filePath)
            ->andReturn(true);

        Storage::shouldReceive('size')
            ->once()
            ->with($this->filePath)
            ->andReturn(1024);

        $this->logger->shouldReceive('info')->twice(); // Starting and processing logs

        $exception = new \Exception('Processing failed');
        $this->csvProcessingService->shouldReceive('processCsvFile')
            ->once()
            ->with($this->filePath, $this->userId)
            ->andThrow($exception);

        $this->logger->shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'CSV import job failed' &&
                       $context['user_id'] === $this->userId &&
                       $context['file_path'] === $this->filePath &&
                       $context['error'] === 'Processing failed' &&
                       $context['attempt'] === 1 &&
                       $context['max_tries'] === 3;
            });

        // File should NOT be deleted on first attempt (as it will retry)
        $this->fileUploadService->shouldNotReceive('deleteFile');

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Processing failed');

        $this->job->handle(
            $this->csvProcessingService,
            $this->fileUploadService,
            $this->logger
        );
    }

    /** @test */
    public function it_handles_processing_failure_and_cleanup_logic()
    {
        // Arrange
        Storage::shouldReceive('exists')
            ->once()
            ->with($this->filePath)
            ->andReturn(true);

        Storage::shouldReceive('size')
            ->once()
            ->with($this->filePath)
            ->andReturn(1024);

        $this->logger->shouldReceive('info')->twice();

        $exception = new \Exception('Processing failed');
        $this->csvProcessingService->shouldReceive('processCsvFile')
            ->once()
            ->andThrow($exception);

        $this->logger->shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'CSV import job failed' &&
                       $context['user_id'] === $this->userId &&
                       $context['file_path'] === $this->filePath &&
                       $context['error'] === 'Processing failed';
            });

        // The job won't delete file on first attempt (since attempts() < tries)
        $this->fileUploadService->shouldNotReceive('deleteFile');

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Processing failed');

        $this->job->handle(
            $this->csvProcessingService,
            $this->fileUploadService,
            $this->logger
        );
    }

    /** @test */
    public function it_logs_job_permanently_failed()
    {
        // Arrange
        $exception = new \Exception('Permanent failure');

        // Mock app() function for LoggerInterface
        $this->app->bind(LoggerInterface::class, function () {
            return $this->logger;
        });

        $this->logger->shouldReceive('error')
            ->once()
            ->with('CSV import job permanently failed', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'error' => 'Permanent failure'
            ]);

        // Act
        $this->job->failed($exception);

        // Assert - Verification is done through mocks
        $this->assertTrue(true);
    }

    /** @test */
    public function it_has_correct_job_configuration()
    {
        // Assert
        $this->assertEquals(600, $this->job->timeout);
        $this->assertEquals(3, $this->job->tries);
        $this->assertEquals(3, $this->job->maxExceptions);
        $this->assertEquals([10, 30, 60], $this->job->backoff);
    }

    /** @test */
    public function it_logs_processing_time()
    {
        // Arrange
        Storage::shouldReceive('exists')->andReturn(true);
        Storage::shouldReceive('size')->andReturn(1024);

        $this->logger->shouldReceive('info')->twice(); // Starting and processing logs

        $this->csvProcessingService->shouldReceive('processCsvFile')
            ->once()
            ->andReturnUsing(function () {
                // Simulate some processing time
                usleep(10000); // 10ms
                return true;
            });

        $this->logger->shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'CSV import job completed successfully' &&
                       isset($context['processing_time']) &&
                       str_contains($context['processing_time'], 's');
            });

        $this->fileUploadService->shouldReceive('deleteFile')->once();

        // Act
        $this->job->handle(
            $this->csvProcessingService,
            $this->fileUploadService,
            $this->logger
        );

        // Assert - Verification is done through mocks
        $this->assertTrue(true);
    }
}
