<?php

namespace Tests\Feature;

use App\Events\EmployeeUpdated;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = Passport::actingAs(
            User::factory()->create(),
            ['create-servers']
        );
    }

    public function test_csv_upload_returns_job_id()
    {
        Queue::fake();

        $csvContent = "name,email,document,city,state,start_date\n";
        $csvContent .= "John Doe,john@example.com,11144477735,São Paulo,SP,2024-01-01\n";
        
        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $response = $this->postJson(route('employees.upload'), [
            'employees' => $file
        ]);

        $response->assertStatus(202)
                ->assertJsonStructure([
                    'message',
                    'job_id'
                ]);

        Queue::assertPushed(\App\Jobs\ProcessEmployeeCsvFile::class);
    }

    public function test_csv_upload_validation_fails_without_file()
    {
        $response = $this->postJson(route('employees.upload'), []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['employees']);
    }

    public function test_csv_upload_validation_fails_with_invalid_file_type()
    {
        $file = UploadedFile::fake()->create('employees.pdf', 100); // Use PDF which is not allowed

        $response = $this->postJson(route('employees.upload'), [
            'employees' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['employees']);
    }

    public function test_csv_upload_validation_fails_with_large_file()
    {
        $file = UploadedFile::fake()->create('employees.csv', 15000); // 15MB

        $response = $this->postJson(route('employees.upload'), [
            'employees' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['employees']);
    }

    public function test_csv_processing_creates_new_employees()
    {
        Event::fake();

        $csvContent = "name,email,document,city,state,start_date\n";
        $csvContent .= "John Doe,john@example.com,11144477735,São Paulo,SP,2024-01-01\n";
        $csvContent .= "Jane Smith,jane@example.com,98765432100,Rio de Janeiro,RJ,2024-01-02\n";
        
        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $service = app(\App\Services\CsvImportService::class);
        $results = $service->processCsvFile($file, $this->user->id);

        $this->assertEquals(2, $results['total_records']);
        $this->assertEquals(2, $results['successful_records']);
        $this->assertEquals(0, $results['failed_records']);
        $this->assertEmpty($results['errors']);

        $this->assertDatabaseHas('employees', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '11144477735',
            'user_id' => $this->user->id
        ]);

        $this->assertDatabaseHas('employees', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'document' => '98765432100',
            'user_id' => $this->user->id
        ]);

        // Should not trigger update events for new employees
        Event::assertNotDispatched(EmployeeUpdated::class);
    }

    public function test_csv_processing_updates_existing_employees()
    {
        Event::fake();

        // Create an existing employee
        Employee::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.old@example.com',
            'document' => '11144477735',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => '2023-01-01',
            'user_id' => $this->user->id
        ]);

        $csvContent = "name,email,document,city,state,start_date\n";
        $csvContent .= "John Doe Updated,john.new@example.com,11144477735,Rio de Janeiro,RJ,2024-01-01\n";
        
        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $service = app(\App\Services\CsvImportService::class);
        $results = $service->processCsvFile($file, $this->user->id);

        $this->assertEquals(1, $results['total_records']);
        $this->assertEquals(1, $results['successful_records']);
        $this->assertEquals(0, $results['failed_records']);

        // Check that the employee was updated
        $this->assertDatabaseHas('employees', [
            'name' => 'John Doe Updated',
            'email' => 'john.new@example.com',
            'document' => '11144477735',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
            'user_id' => $this->user->id
        ]);

        // Should trigger update event for existing employees
        Event::assertDispatched(EmployeeUpdated::class);
    }

    public function test_csv_processing_handles_invalid_data()
    {
        $csvContent = "name,email,document,city,state,start_date\n";
        $csvContent .= "John Doe,john@example.com,11144477735,São Paulo,SP,2024-01-01\n";
        $csvContent .= "Jane,invalid-email,12345678901,Rio de Janeiro,RJ,2024-01-02\n"; // Invalid email and CPF
        $csvContent .= ",jane2@example.com,98765432100,Belo Horizonte,MG,2024-01-03\n"; // Empty name
        
        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $service = app(\App\Services\CsvImportService::class);
        $results = $service->processCsvFile($file, $this->user->id);

        $this->assertEquals(3, $results['total_records']);
        $this->assertEquals(1, $results['successful_records']);
        $this->assertEquals(2, $results['failed_records']);
        $this->assertCount(2, $results['errors']);

        // Only valid employee should be saved
        $this->assertDatabaseHas('employees', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '11144477735'
        ]);

        $this->assertDatabaseMissing('employees', [
            'name' => 'Jane'
        ]);
    }

    public function test_csv_processing_fails_with_invalid_header()
    {
        $csvContent = "invalid,header,columns\n";
        $csvContent .= "John Doe,john@example.com,11144477735\n";
        
        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $service = app(\App\Services\CsvImportService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid CSV header');

        $service->processCsvFile($file, $this->user->id);
    }
}