<?php

namespace Tests\Unit;

use App\Jobs\ProcessEmployeeCsvFile;
use App\Services\CsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProcessEmployeeCsvFileTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_processes_csv_file_successfully()
    {
        Storage::fake();

        // Create a test CSV file
        $csvContent = "name,email,document,city,state,start_date\n";
        $csvContent .= "John Doe,john@example.com,11144477735,São Paulo,SP,2024-01-01\n";
        
        $filePath = 'temp/test_file.csv';
        Storage::put($filePath, $csvContent);

        // Mock the CSV import service
        $serviceMock = Mockery::mock(CsvImportService::class);
        $serviceMock->shouldReceive('processCsvFile')
            ->once()
            ->with(Mockery::type(\Illuminate\Http\UploadedFile::class), 1)
            ->andReturn([
                'total_records' => 1,
                'successful_records' => 1,
                'failed_records' => 0,
                'errors' => []
            ]);

        $this->app->instance(CsvImportService::class, $serviceMock);

        $job = new ProcessEmployeeCsvFile($filePath, 1, 'test-job-id');
        $job->handle($serviceMock);

        // Verify the file was deleted after processing
        Storage::assertMissing($filePath);
    }

    public function test_job_cleans_up_file_on_failure()
    {
        Storage::fake();

        $filePath = 'temp/test_file.csv';
        Storage::put($filePath, 'test content');

        // Mock the service to throw an exception
        $serviceMock = Mockery::mock(CsvImportService::class);
        $serviceMock->shouldReceive('processCsvFile')
            ->once()
            ->andThrow(new \Exception('Processing failed'));

        $job = new ProcessEmployeeCsvFile($filePath, 1, 'test-job-id');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Processing failed');

        $job->handle($serviceMock);

        // Verify the file was deleted even on failure
        Storage::assertMissing($filePath);
    }

    public function test_job_failed_method_cleans_up_file()
    {
        Storage::fake();

        $filePath = 'temp/test_file.csv';
        Storage::put($filePath, 'test content');

        $job = new ProcessEmployeeCsvFile($filePath, 1, 'test-job-id');
        $job->failed(new \Exception('Job failed'));

        // Verify the file was deleted
        Storage::assertMissing($filePath);
    }
}