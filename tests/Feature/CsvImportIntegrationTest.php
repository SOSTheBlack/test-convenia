<?php

namespace Tests\Feature;

use App\Jobs\Employees\ProcessEmployeeCsvFileJob;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvImportIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    /**
     * Testa fluxo completo: Upload → Job dispatch → Validação
     */
    public function test_complete_csv_upload_to_job_dispatch_flow(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $csvData = [
            $this->validEmployeeData(['email' => 'employee1@example.com']),
            $this->validEmployeeData(['email' => 'employee2@example.com']),
            $this->validEmployeeData(['email' => 'employee3@example.com']),
        ];
        $csvFile = $this->createCsvFile($csvData);

        // Act - Upload do arquivo
        $uploadResponse = $this->postJson('/api/employees', [
            'file' => $csvFile,
        ]);

        // Assert - Upload foi bem-sucedido
        $uploadResponse->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'file_path',
            ]);

        $filePath = $uploadResponse->json('file_path');
        $this->assertNotEmpty($filePath);

        // Assert - Arquivo foi salvo
        Storage::assertExists($filePath);

        // Assert - Job foi disparado
        Queue::assertPushed(ProcessEmployeeCsvFileJob::class, function ($job) use ($user, $filePath) {
            return $job->userId === $user->id && $job->filePath === $filePath;
        });
    }

    /**
     * Testa que diferentes usuários recebem jobs isolados
     */
    public function test_csv_upload_isolation_between_users(): void
    {
        // Arrange
        $user1 = $this->authenticatedUser();
        $csvFile1 = $this->createCsvFile([$this->validEmployeeData(['email' => 'user1@example.com'])]);

        // Act - Upload pelo primeiro usuário
        $response1 = $this->postJson('/api/employees', ['file' => $csvFile1]);

        // Arrange - Segundo usuário
        $user2 = User::factory()->create();
        $this->authenticatedUser($user2);
        $csvFile2 = $this->createCsvFile([$this->validEmployeeData(['email' => 'user2@example.com'])]);

        // Act - Upload pelo segundo usuário
        $response2 = $this->postJson('/api/employees', ['file' => $csvFile2]);

        // Assert
        $response1->assertStatus(Response::HTTP_OK);
        $response2->assertStatus(Response::HTTP_OK);

        // Verifica que jobs foram criados para usuários corretos
        Queue::assertPushed(ProcessEmployeeCsvFileJob::class, function ($job) use ($user1) {
            return $job->userId === $user1->id;
        });

        Queue::assertPushed(ProcessEmployeeCsvFileJob::class, function ($job) use ($user2) {
            return $job->userId === $user2->id;
        });

        // Verifica que cada usuário tem um arquivo separado
        $filePath1 = $response1->json('file_path');
        $filePath2 = $response2->json('file_path');
        $this->assertNotEquals($filePath1, $filePath2);
    }

    /**
     * Testa upload sequencial de múltiplos arquivos
     */
    public function test_multiple_csv_uploads_by_same_user(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        $uploads = [
            $this->createCsvFile([$this->validEmployeeData(['email' => 'batch1@example.com'])]),
            $this->createCsvFile([$this->validEmployeeData(['email' => 'batch2@example.com'])]),
            $this->createCsvFile([$this->validEmployeeData(['email' => 'batch3@example.com'])]),
        ];

        $filePaths = [];

        // Act - Multiple uploads
        foreach ($uploads as $csvFile) {
            $response = $this->postJson('/api/employees', ['file' => $csvFile]);
            $response->assertStatus(Response::HTTP_OK);
            $filePaths[] = $response->json('file_path');
        }

        // Assert - Todos os jobs foram disparados
        Queue::assertPushed(ProcessEmployeeCsvFileJob::class, 3);

        // Assert - Cada upload gerou um arquivo único
        $this->assertCount(3, array_unique($filePaths));

        // Assert - Todos os arquivos foram salvos
        foreach ($filePaths as $filePath) {
            Storage::assertExists($filePath);
        }
    }

    /**
     * Testa integração com sistema de autenticação
     */
    public function test_csv_upload_requires_valid_authentication(): void
    {
        // Arrange
        $csvFile = $this->createCsvFile([$this->validEmployeeData()]);

        // Act - Tentativa sem autenticação
        $response = $this->postJson('/api/employees', ['file' => $csvFile]);

        // Assert
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        Queue::assertNothingPushed();
    }

    /**
     * Testa validação de arquivo durante upload
     */
    public function test_csv_upload_validation_rules(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        $testCases = [
            // Sem arquivo
            [
                'data' => [],
                'expectedErrors' => ['file'],
            ],
            // Arquivo muito grande (simulado)
            [
                'data' => ['file' => $this->createLargeFile()],
                'expectedErrors' => ['file'],
            ],
            // Arquivo não CSV
            [
                'data' => ['file' => $this->createNonCsvFile()],
                'expectedErrors' => ['file'],
            ],
        ];

        foreach ($testCases as $testCase) {
            // Act
            $response = $this->postJson('/api/employees', $testCase['data']);

            // Assert
            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors($testCase['expectedErrors']);
        }

        // Assert - Nenhum job foi disparado para uploads inválidos
        Queue::assertNothingPushed();
    }

    /**
     * Testa resposta da API após upload bem-sucedido
     */
    public function test_successful_upload_api_response(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $csvData = [
            $this->validEmployeeData(['email' => 'test1@example.com']),
            $this->validEmployeeData(['email' => 'test2@example.com']),
        ];
        $csvFile = $this->createCsvFile($csvData);

        // Act
        $response = $this->postJson('/api/employees', ['file' => $csvFile]);

        // Assert - Estrutura da resposta
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'file_path',
            ]);

        // Assert - Conteúdo da resposta
        $this->assertStringContainsString('sucesso', $response->json('message'));
        $this->assertStringStartsWith('csv/employees/', $response->json('file_path'));
    }

    /**
     * Testa que arquivos CSV são salvos com nomes únicos
     */
    public function test_csv_files_saved_with_unique_names(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $csvFile = $this->createCsvFile([$this->validEmployeeData()]);

        $responses = [];

        // Act - Upload do mesmo arquivo múltiplas vezes
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/employees', ['file' => $csvFile]);
            $response->assertStatus(Response::HTTP_OK);
            $responses[] = $response->json('file_path');
        }

        // Assert - Cada upload gerou um caminho único
        $this->assertCount(3, array_unique($responses));

        // Assert - Todos os arquivos existem
        foreach ($responses as $filePath) {
            Storage::assertExists($filePath);
        }
    }

    /**
     * Testa integração com listagem de funcionários após upload
     */
    public function test_employee_listing_after_csv_upload(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        // Funcionários existentes
        Employee::factory()->count(2)->forUser($user)->create();

        $csvFile = $this->createCsvFile([
            $this->validEmployeeData(['email' => 'new1@example.com']),
            $this->validEmployeeData(['email' => 'new2@example.com']),
        ]);

        // Act - Upload
        $uploadResponse = $this->postJson('/api/employees', ['file' => $csvFile]);

        // Act - Listagem (antes do processamento)
        $listResponse = $this->getJson('/api/employees');

        // Assert - Upload foi bem-sucedido
        $uploadResponse->assertStatus(Response::HTTP_OK);

        // Assert - Listagem ainda mostra apenas funcionários existentes
        $listResponse->assertStatus(Response::HTTP_OK);
        $this->assertCount(2, $listResponse->json('data'));

        // Assert - Job foi agendado para processamento futuro
        Queue::assertPushed(ProcessEmployeeCsvFileJob::class);
    }

    /**
     * Helper para criar arquivo grande simulado
     */
    private function createLargeFile()
    {
        return \Illuminate\Http\UploadedFile::fake()->create('large.csv', 10240, 'text/csv'); // 10MB
    }

    /**
     * Helper para criar arquivo não CSV
     */
    private function createNonCsvFile()
    {
        return \Illuminate\Http\UploadedFile::fake()->create('document.txt', 100, 'text/plain');
    }
}
