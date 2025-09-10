# Estrutura do Projeto

## Organização de Diretórios

Abaixo está a estrutura de diretórios recomendada para o projeto, com explicações sobre cada componente:

```
test-convenia/
├── app/
│   ├── Console/
│   │   └── Commands/                # Comandos personalizados
│   ├── Exceptions/                  # Exceções personalizadas
│   │   ├── CsvImportException.php
│   │   └── EmployeeValidationException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/
│   │   │       ├── AuthController.php
│   │   │       ├── EmployeeController.php
│   │   │       └── ImportStatusController.php
│   │   ├── Middleware/             # Middleware personalizado
│   │   └── Requests/               # Form Requests para validação
│   │       ├── AuthLoginRequest.php
│   │       └── EmployeeImportRequest.php
│   ├── Jobs/                       # Jobs para processamento assíncrono
│   │   └── ProcessEmployeeCsvFile.php
│   ├── Mail/                       # Classes para envio de e-mails
│   │   └── EmployeeUpdateNotification.php
│   ├── Models/                     # Modelos Eloquent
│   │   ├── Employee.php
│   │   ├── ImportError.php
│   │   ├── ImportJob.php
│   │   └── User.php
│   ├── DTO/                        # Data Transfer Objects
│   │   └── EmployeeData.php
│   ├── Events/                     # Eventos da aplicação
│   │   └── EmployeeUpdated.php
│   ├── Listeners/                  # Listeners para eventos
│   │   └── SendEmployeeUpdateNotification.php
│   ├── Repositories/               # Padrão Repository
│   │   ├── Contracts/              # Interfaces dos repositories
│   │   │   └── EmployeeRepositoryInterface.php
│   │   └── Eloquent/               # Implementações Eloquent
│   │       └── EloquentEmployeeRepository.php
│   └── Services/                   # Services para lógica de negócio
│       ├── CsvImportService.php
│       └── EmployeeService.php
├── bootstrap/
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── queue.php                   # Configuração das filas
│   └── ...
├── database/
│   ├── factories/                  # Factories para testes
│   │   ├── EmployeeFactory.php
│   │   └── UserFactory.php
│   ├── migrations/                 # Migrações de banco de dados
│   │   ├── 2014_10_12_000000_create_users_table.php
│   │   ├── 2023_10_10_000000_create_employees_table.php
│   │   ├── 2023_10_10_000001_create_import_jobs_table.php
│   │   └── 2023_10_10_000002_create_import_errors_table.php
│   └── seeders/                    # Seeders para dados iniciais
│       └── DatabaseSeeder.php
├── public/
├── resources/
│   ├── views/
│   │   └── emails/                 # Templates de e-mail
│   │       └── employee-update.blade.php
│   └── ...
├── routes/
│   ├── api.php                     # Rotas da API
│   └── ...
├── storage/
├── tests/                          # Testes da aplicação
│   ├── Feature/                    # Testes de feature/integração
│   │   ├── API/
│   │   │   ├── AuthTest.php
│   │   │   └── EmployeeImportTest.php
│   │   └── Jobs/
│   │       └── ProcessEmployeeCsvFileTest.php
│   ├── Unit/                       # Testes unitários
│   │   ├── DTO/
│   │   │   └── EmployeeDataTest.php
│   │   ├── Repositories/
│   │   │   └── EloquentEmployeeRepositoryTest.php
│   │   ├── Services/
│   │   │   ├── CsvImportServiceTest.php
│   │   │   └── EmployeeServiceTest.php
│   │   └── ...
│   └── TestCase.php
├── vendor/
├── .env.example
├── .gitignore
├── artisan
├── composer.json
├── docker-compose.yml              # Configuração Docker
├── Dockerfile                      # Configuração Docker
├── phpunit.xml                     # Configuração PHPUnit
├── README.md                       # Documentação do projeto
└── phpstan.neon                    # Configuração PHPStan
```

## Explicação dos Componentes

### Controllers

Responsáveis por receber as requisições HTTP, validá-las e orquestrar as ações:

```php
class EmployeeController extends Controller
{
    private EmployeeService $employeeService;
    
    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }
    
    public function import(EmployeeImportRequest $request)
    {
        $file = $request->file('file');
        $user = Auth::user();
        
        $jobId = $this->employeeService->queueCsvImport($file, $user);
        
        return response()->json([
            'message' => 'Arquivo enviado com sucesso e será processado em breve',
            'job_id' => $jobId
        ], 202);
    }
    
    // Outros métodos (index, show, etc.)
}
```

### Repositories

Encapsulam a lógica de acesso e manipulação de dados:

```php
interface EmployeeRepositoryInterface
{
    public function findByDocument(string $document): ?Employee;
    public function create(EmployeeData $data): Employee;
    public function update(Employee $employee, EmployeeData $data): Employee;
    // Outros métodos
}

class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function findByDocument(string $document): ?Employee
    {
        return Employee::where('document', $document)->first();
    }
    
    // Implementação dos outros métodos
}
```

### Services

Contêm a lógica de negócio da aplicação:

```php
class EmployeeService
{
    private EmployeeRepositoryInterface $repository;
    
    public function __construct(EmployeeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
    
    public function queueCsvImport(UploadedFile $file, User $user): string
    {
        // Validar arquivo
        // Gerar ID único para o job
        $jobId = (string) Str::uuid();
        
        // Salvar arquivo temporariamente
        $path = $file->store('csv_imports');
        
        // Criar registro do job
        $importJob = new ImportJob([
            'job_id' => $jobId,
            'status' => 'pending',
            'file_name' => $file->getClientOriginalName(),
            'user_id' => $user->id
        ]);
        $importJob->save();
        
        // Despachar job para fila
        ProcessEmployeeCsvFile::dispatch($path, $user, $jobId);
        
        return $jobId;
    }
    
    // Outros métodos para gerenciar funcionários
}
```

### DTOs (Data Transfer Objects)

Transportam dados de maneira estruturada entre as camadas:

```php
class EmployeeData
{
    public readonly string $name;
    public readonly string $email;
    public readonly string $document;
    public readonly ?string $phone;
    public readonly DateTime $birthDate;
    public readonly float $salary;
    public readonly string $department;
    public ?int $userId = null;
    
    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->document = $this->formatDocument($data['document']);
        $this->phone = $data['phone'] ?? null;
        $this->birthDate = new DateTime($data['birth_date']);
        $this->salary = (float) $data['salary'];
        $this->department = $data['department'];
    }
    
    private function formatDocument(string $document): string
    {
        // Remover caracteres não numéricos
        return preg_replace('/[^0-9]/', '', $document);
    }
    
    // Métodos de validação, etc.
}
```

### Jobs

Processamento assíncrono em segundo plano:

```php
class ProcessEmployeeCsvFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    private string $filePath;
    private User $user;
    private string $jobId;
    
    public function __construct(string $filePath, User $user, string $jobId)
    {
        $this->filePath = $filePath;
        $this->user = $user;
        $this->jobId = $jobId;
    }
    
    public function handle(CsvImportService $csvService)
    {
        try {
            // Atualizar status do job
            $importJob = ImportJob::where('job_id', $this->jobId)->firstOrFail();
            $importJob->update(['status' => 'processing']);
            
            // Processar CSV
            $result = $csvService->process($this->filePath, $this->user, $this->jobId);
            
            // Atualizar status do job
            $importJob->update([
                'status' => 'completed',
                'total_records' => $result['total'],
                'processed_records' => $result['processed'],
                'successful_records' => $result['successful'],
                'failed_records' => $result['failed']
            ]);
            
        } catch (Exception $e) {
            // Registrar erro e atualizar status
            Log::error('Falha no processamento do CSV: ' . $e->getMessage(), [
                'job_id' => $this->jobId,
                'exception' => $e
            ]);
            
            if ($importJob) {
                $importJob->update(['status' => 'failed']);
            }
            
            throw $e;
        } finally {
            // Limpar arquivo temporário
            Storage::delete($this->filePath);
        }
    }
}
```

### Events e Listeners

Comunicação entre componentes desacoplados:

```php
class EmployeeUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public Employee $employee;
    public bool $isNewRecord;
    
    public function __construct(Employee $employee, bool $isNewRecord)
    {
        $this->employee = $employee;
        $this->isNewRecord = $isNewRecord;
    }
}

class SendEmployeeUpdateNotification implements ShouldQueue
{
    use InteractsWithQueue;
    
    public function handle(EmployeeUpdated $event)
    {
        $user = $event->employee->user;
        $action = $event->isNewRecord ? 'criado' : 'atualizado';
        
        Mail::to($user->email)
            ->send(new EmployeeUpdateNotification($event->employee, $action));
    }
}
```

### Rotas

Definem os endpoints da API:

```php
// routes/api.php
Route::middleware('auth:api')->group(function () {
    // Rota para importação de CSV
    Route::post('/employees', [EmployeeController::class, 'import']);
    
    // Rota para verificar status da importação
    Route::get('/import-status/{jobId}', [ImportStatusController::class, 'show']);
    
    // Rota para listar funcionários
    Route::get('/employees', [EmployeeController::class, 'index']);
});

// Rotas públicas
Route::post('/auth/login', [AuthController::class, 'login']);
```

### Service Providers

Registro e configuração dos componentes:

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->bind(
        EmployeeRepositoryInterface::class,
        EloquentEmployeeRepository::class
    );
}
```

## Fluxo de Processamento

1. **Upload do arquivo CSV**:
   - Cliente faz upload via API
   - Controller valida o arquivo
   - Service gera ID para o job e salva o arquivo
   - Job é despachado para processamento assíncrono

2. **Processamento do CSV**:
   - Job inicia o processamento
   - CsvImportService lê o arquivo linha por linha
   - Cada linha é convertida em EmployeeData (DTO)
   - DTO é validado
   - EmployeeService cria ou atualiza funcionários
   - Evento é disparado para cada funcionário atualizado

3. **Notificação**:
   - Listener recebe o evento
   - E-mail é enviado ao usuário

4. **Monitoramento**:
   - Cliente pode consultar o status da importação
   - Erros são registrados para diagnóstico