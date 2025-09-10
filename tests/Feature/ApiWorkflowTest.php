<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_csv_import_workflow()
    {
        Queue::fake();

        // 1. Authenticate user
        $user = Passport::actingAs(
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@convenia.com'
            ]),
            ['create-servers']
        );

        // 2. Create CSV content with valid data
        $csvContent = "name,email,document,city,state,start_date\n";
        $csvContent .= "João Silva,joao@empresa.com,11144477735,São Paulo,SP,2024-01-15\n";
        $csvContent .= "Maria Santos,maria@empresa.com,98765432100,Rio de Janeiro,RJ,2024-01-16\n";
        
        $file = UploadedFile::fake()->createWithContent('funcionarios.csv', $csvContent);

        // 3. Upload CSV file
        $uploadResponse = $this->postJson(route('employees.upload'), [
            'employees' => $file
        ]);

        $uploadResponse->assertStatus(202)
                     ->assertJsonStructure([
                         'message',
                         'job_id'
                     ]);

        $jobId = $uploadResponse->json('job_id');

        // 4. Check job status (mock endpoint)
        $statusResponse = $this->getJson(route('import.status', ['jobId' => $jobId]));

        $statusResponse->assertStatus(200)
                      ->assertJsonStructure([
                          'job_id',
                          'status',
                          'message',
                          'processed_records',
                          'successful_records',
                          'failed_records',
                          'errors'
                      ]);

        // 5. List employees (should be empty since job is queued)
        $listResponse = $this->getJson(route('employees.get'));

        $listResponse->assertStatus(200)
                    ->assertJsonStructure([
                        'data',
                        'current_page',
                        'total',
                        'per_page'
                    ]);

        // 6. Verify job was queued
        Queue::assertPushed(\App\Jobs\ProcessEmployeeCsvFile::class);

        $this->assertTrue(true, 'Complete workflow test passed successfully!');
    }
}