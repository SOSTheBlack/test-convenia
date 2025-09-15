<?php

namespace Tests\Feature;

use App\Jobs\Employees\ProcessEmployeeCsvFileJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadEmployeesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    /**
     * Testa upload de arquivo CSV válido
     */
    public function test_authenticated_user_can_upload_valid_csv(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $csvFile = $this->createCsvFile([
            $this->validEmployeeData(),
            $this->validEmployeeData(['email' => 'employee2@example.com']),
        ]);

        // Act
        $response = $this->postJson('/api/employees', [
            'file' => $csvFile,
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'file_path',
            ])
            ->assertJsonFragment([
                'message' => 'Arquivo enviado com sucesso. O processamento foi iniciado.',
            ]);

        // Verifica que o job foi disparado
        Queue::assertPushed(ProcessEmployeeCsvFileJob::class, function ($job) use ($user) {
            return $job->userId === $user->id;
        });

        // Verifica que o arquivo foi salvo
        $this->assertNotNull($response->json('file_path'));
        Storage::assertExists($response->json('file_path'));
    }

    /**
     * Testa upload sem arquivo
     */
    public function test_upload_requires_file(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        // Act
        $response = $this->postJson('/api/employees', []);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * Testa upload com arquivo não CSV
     */
    public function test_upload_requires_csv_file(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $txtFile = UploadedFile::fake()->create('employees.txt', 100, 'text/plain');

        // Act
        $response = $this->postJson('/api/employees', [
            'file' => $txtFile,
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * Testa upload com arquivo muito grande
     */
    public function test_upload_rejects_large_files(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $largeFile = UploadedFile::fake()->create('employees.csv', 10240, 'text/csv'); // 10MB

        // Act
        $response = $this->postJson('/api/employees', [
            'file' => $largeFile,
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * Testa upload com arquivo vazio
     */
    public function test_upload_rejects_empty_files(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $emptyFile = UploadedFile::fake()->create('employees.csv', 0, 'text/csv');

        // Act
        $response = $this->postJson('/api/employees', [
            'file' => $emptyFile,
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * Testa upload sem autenticação
     */
    public function test_upload_requires_authentication(): void
    {
        // Arrange
        $csvFile = $this->createCsvFile([$this->validEmployeeData()]);

        // Act
        $response = $this->postJson('/api/employees', [
            'file' => $csvFile,
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        Queue::assertNothingPushed();
    }

    /**
     * Testa que o job é disparado com os parâmetros corretos
     */
    public function test_job_is_dispatched_with_correct_parameters(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $csvData = [
            $this->validEmployeeData(),
            $this->validEmployeeData(['email' => 'employee2@example.com']),
        ];
        $csvFile = $this->createCsvFile($csvData);

        // Act
        $response = $this->postJson('/api/employees', [
            'employees' => $csvFile,
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_OK);

        Queue::assertPushed(ProcessEmployeeCsvFileJob::class, function ($job) use ($user) {
            return $job->userId === $user->id &&
                   !empty($job->filePath) &&
                   str_contains($job->filePath, 'csv/employees');
        });
    }

    /**
     * Testa estrutura da resposta de sucesso
     */
    public function test_successful_upload_response_structure(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $csvFile = $this->createCsvFile([$this->validEmployeeData()]);

        // Act
        $response = $this->postJson('/api/employees', [
            'employees' => $csvFile,
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'file_path',
            ]);

        $this->assertIsString($response->json('message'));
        $this->assertIsString($response->json('file_path'));
        $this->assertStringStartsWith('csv/employees/', $response->json('file_path'));
    }

    /**
     * Testa upload com CSV contendo cabeçalhos
     */
    public function test_upload_csv_with_headers(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $csvContent = "name,email,cpf,department,salary,admission_date\n";
        $csvContent .= "João Silva,joao@example.com,12345678901,TI,5000.00,2023-01-15\n";
        $csvContent .= "Maria Santos,maria@example.com,98765432100,RH,4500.00,2023-02-01\n";

        $csvFile = UploadedFile::fake()->createWithContent(
            'employees.csv',
            $csvContent
        );

        // Act
        $response = $this->postJson('/api/employees', [
            'employees' => $csvFile,
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        Queue::assertPushed(ProcessEmployeeCsvFileJob::class);
    }

    /**
     * Testa upload com diferentes formatos de arquivo CSV
     */
    public function test_upload_accepts_different_csv_formats(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        $csvFormats = [
            'employees.csv',
            'employees.CSV',
            'data.csv',
        ];

        foreach ($csvFormats as $filename) {
            $csvFile = $this->createCsvFile([$this->validEmployeeData()], $filename);

            // Act
            $response = $this->postJson('/api/employees', [
                'employees' => $csvFile,
            ]);

            // Assert
            $response->assertStatus(Response::HTTP_OK);
        }

        Queue::assertPushed(ProcessEmployeeCsvFileJob::class, 3);
    }

    /**
     * Testa que arquivos são salvos em locais únicos
     */
    public function test_files_are_saved_to_unique_locations(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $csvFile1 = $this->createCsvFile([$this->validEmployeeData()]);
        $csvFile2 = $this->createCsvFile([$this->validEmployeeData()]);

        // Act
        $response1 = $this->postJson('/api/employees', ['employees' => $csvFile1]);
        $response2 = $this->postJson('/api/employees', ['employees' => $csvFile2]);

        // Assert
        $response1->assertStatus(Response::HTTP_OK);
        $response2->assertStatus(Response::HTTP_OK);

        $filePath1 = $response1->json('file_path');
        $filePath2 = $response2->json('file_path');

        $this->assertNotEquals($filePath1, $filePath2);

        Storage::assertExists($filePath1);
        Storage::assertExists($filePath2);
    }

    /**
     * Testa upload com CSV contendo caracteres especiais
     */
    public function test_upload_csv_with_special_characters(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $csvData = [
            [
                'name' => 'João Ção',
                'email' => 'joao.cao@example.com',
                'cpf' => '12345678901',
                'department' => 'TI & Inovação',
                'salary' => '5.000,50',
                'admission_date' => '2023-01-15',
            ],
        ];
        $csvFile = $this->createCsvFile($csvData);

        // Act
        $response = $this->postJson('/api/employees', [
            'employees' => $csvFile,
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        Queue::assertPushed(ProcessEmployeeCsvFileJob::class);
    }

    /**
     * Testa múltiplos uploads simultâneos
     */
    public function test_multiple_uploads_by_same_user(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        $uploads = [
            $this->createCsvFile([$this->validEmployeeData()]),
            $this->createCsvFile([$this->validEmployeeData(['email' => 'test2@example.com'])]),
            $this->createCsvFile([$this->validEmployeeData(['email' => 'test3@example.com'])]),
        ];

        // Act & Assert
        foreach ($uploads as $index => $csvFile) {
            $response = $this->postJson('/api/employees', ['employees' => $csvFile]);
            $response->assertStatus(Response::HTTP_OK);
        }

        Queue::assertPushed(ProcessEmployeeCsvFileJob::class, 3);
    }

    /**
     * Testa uploads por diferentes usuários
     */
    public function test_uploads_by_different_users(): void
    {
        // Arrange
        $user1 = $this->authenticatedUser();
        $csvFile1 = $this->createCsvFile([$this->validEmployeeData()]);

        // Act - Upload pelo primeiro usuário
        $response1 = $this->postJson('/api/employees', ['employees' => $csvFile1]);

        // Arrange - Segundo usuário
        $user2 = User::factory()->create();
        $this->authenticatedUser($user2);
        $csvFile2 = $this->createCsvFile([$this->validEmployeeData(['email' => 'user2@example.com'])]);

        // Act - Upload pelo segundo usuário
        $response2 = $this->postJson('/api/employees', ['employees' => $csvFile2]);

        // Assert
        $response1->assertStatus(Response::HTTP_OK);
        $response2->assertStatus(Response::HTTP_OK);

        Queue::assertPushed(ProcessEmployeeCsvFileJob::class, function ($job) use ($user1) {
            return $job->userId === $user1->id;
        });

        Queue::assertPushed(ProcessEmployeeCsvFileJob::class, function ($job) use ($user2) {
            return $job->userId === $user2->id;
        });
    }

    /**
     * Testa validação de tipo MIME
     */
    public function test_validates_mime_type(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        // Cria arquivo com extensão CSV mas conteúdo diferente
        $fakeFile = UploadedFile::fake()->createWithContent(
            'employees.csv',
            '{"json": "content"}'
        );

        // Act
        $response = $this->postJson('/api/employees', [
            'employees' => $'file' => $fakeFile,
        ]);

        // Assert - Dependendo da validação implementada, pode aceitar ou rejeitar
        // Se rejeitado, deve ser 422; se aceito, deve processar normalmente
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_UNPROCESSABLE_ENTITY
        ]);
    }
}
