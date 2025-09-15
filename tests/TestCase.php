<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurações globais para testes
        Storage::fake('local');
        Mail::fake();
        Event::fake();
        Queue::fake();

        // Configura Passport para testes
        \Laravel\Passport\Passport::loadKeysFrom(__DIR__ . '/../storage');

        // Cria o personal access client se não existir
        if (!\Laravel\Passport\Client::where('personal_access_client', true)->exists()) {
            \Laravel\Passport\PersonalAccessClient::create([
                'client_id' => \Laravel\Passport\Client::create([
                    'user_id' => null,
                    'name' => 'Laravel Personal Access Client',
                    'secret' => 'secret',
                    'provider' => null,
                    'redirect' => 'http://localhost',
                    'personal_access_client' => true,
                    'password_client' => false,
                    'revoked' => false,
                ])->id,
            ]);
        }
    }

    /**
     * Create an authenticated user for API tests
     */
    protected function authenticatedUser(): User
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->accessToken;
        $this->withHeaders(['Authorization' => 'Bearer ' . $token]);
        return $user;
    }

    /**
     * Create a CSV file for testing
     */
    protected function createCsvFile(array $data, string $filename = 'employees.csv'): UploadedFile
    {
        $content = "name,email,document,city,state,start_date\n";
        foreach ($data as $row) {
            $content .= implode(',', $row) . "\n";
        }

        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    /**
     * Create a valid employee CSV data array
     */
    protected function validEmployeeData(): array
    {
        return [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '12345678901',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => '2023-01-15'
        ];
    }

    /**
     * Create multiple valid employee data rows
     */
    protected function validEmployeesData(): array
    {
        return [
            [
                'Bob Wilson',
                'bob@paopaocafe.com',
                '13001647000',
                'Salvador',
                'BA',
                '2020-01-15'
            ],
            [
                'Laura Matsuda',
                'lmm@atsuda.com.br',
                '60095284028',
                'Niterói',
                'RJ',
                '2019-06-08'
            ],
            [
                'Marco Rodrigues',
                'marco@kyokugen.org',
                '71306511054',
                'Osasco',
                'SP',
                '2021-01-10'
            ]
        ];
    }

    /**
     * Get the stub CSV file
     */
    protected function getStubCsvFile(): UploadedFile
    {
        $stubPath = base_path('tests/Stubs/employees.csv');
        return new UploadedFile(
            $stubPath,
            'employees.csv',
            'text/csv',
            null,
            true
        );
    }

    /**
     * Assert that a CSV import job was created
     */
    protected function assertJobCreated(string $jobId, int $userId): void
    {
        $this->assertDatabaseHas('import_jobs', [
            'job_id' => $jobId,
            'user_id' => $userId,
            'status' => 'pending'
        ]);
    }

    /**
     * Assert that an employee was created with specific data
     */
    protected function assertEmployeeCreated(array $data, int $userId): void
    {
        $this->assertDatabaseHas('employees', array_merge($data, [
            'user_id' => $userId
        ]));
    }
}
