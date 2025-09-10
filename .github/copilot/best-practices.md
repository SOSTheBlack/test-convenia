# Boas Práticas para o Projeto

## Padrões de Codificação

### Geral

- Seguir o padrão PSR-12
- Usar tipagem estrita (declare(strict_types=1))
- Nomes descritivos para classes, métodos e variáveis
- Classes com responsabilidade única (SRP)
- Métodos curtos e focados (máx. ~15 linhas)
- Evitar comentários óbvios, código deve ser autoexplicativo
- Documentar APIs e componentes complexos

### Laravel

- Seguir Laravel Way quando possível
- Utilizar features nativas do Laravel
- Evitar consultas N+1 usando eager loading
- Padronizar nomes de rotas e controllers
- Usar Resource Controllers quando apropriado
- Preferir Eloquent sobre Query Builder quando possível
- Implementar Form Requests para validação

## Design Patterns

### Repository Pattern

```php
// Contrato
interface EmployeeRepositoryInterface
{
    public function findByDocument(string $document): ?Employee;
    public function create(EmployeeData $data): Employee;
    public function update(Employee $employee, EmployeeData $data): Employee;
    public function list(int $page = 1, int $perPage = 15, array $filters = []): LengthAwarePaginator;
}

// Implementação
class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function findByDocument(string $document): ?Employee
    {
        return Employee::where('document', $document)->first();
    }
    
    // Implementações dos outros métodos...
}
```

### Service Pattern

```php
class EmployeeService
{
    private EmployeeRepositoryInterface $repository;
    
    public function __construct(EmployeeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
    
    public function createOrUpdate(EmployeeData $data, User $user): Employee
    {
        $employee = $this->repository->findByDocument($data->document);
        
        if ($employee) {
            return $this->repository->update($employee, $data);
        }
        
        $data->userId = $user->id;
        return $this->repository->create($data);
    }
}
```

### DTO Pattern

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
        $this->document = $data['document'];
        $this->phone = $data['phone'] ?? null;
        $this->birthDate = new DateTime($data['birth_date']);
        $this->salary = (float) $data['salary'];
        $this->department = $data['department'];
    }
    
    public static function fromCsvRow(array $row): self
    {
        // Validação e normalização podem acontecer aqui
        return new self($row);
    }
}
```

## Tratamento de Erros

- Criar exceções personalizadas e específicas
- Capturar e logar exceções apropriadamente
- Retornar mensagens de erro claras e consistentes
- Usar códigos HTTP adequados
- Centralizar tratamento de exceções

```php
class CsvImportException extends Exception
{
    protected $row;
    
    public function __construct(string $message, int $row, int $code = 0, Throwable $previous = null)
    {
        $this->row = $row;
        parent::__construct($message, $code, $previous);
    }
    
    public function getRow(): int
    {
        return $this->row;
    }
}
```

## Performance e Escalabilidade

- Processar arquivos em chunks para evitar problemas de memória
- Usar filas para processamento assíncrono
- Configurar índices de banco de dados adequados
- Implementar cache quando apropriado
- Otimizar consultas Eloquent
- Configurar rate limiting para a API
- Monitorar tempos de resposta e uso de recursos

## Segurança

- Sanitizar e validar todos os inputs
- Usar HTTPS em todos os ambientes
- Implementar autenticação robusta
- Aplicar princípio de privilégio mínimo
- Proteger contra ataques comuns (CSRF, XSS, SQL Injection)
- Auditar e logar ações sensíveis
- Fazer rotação de segredos e tokens
- Validar permissões em cada endpoint
- Implementar políticas de senha seguras

## Convenções de Commits

- Utilizar commits semânticos:
  - `feat`: Nova funcionalidade
  - `fix`: Correção de bug
  - `refactor`: Refatoração de código
  - `docs`: Documentação
  - `test`: Adição/modificação de testes
  - `chore`: Tarefas de manutenção

Exemplo:
```
feat: implementa importação de funcionários via CSV
```

## Convenções de Nomenclatura

- **Controllers**: Singular, sufixo Controller (EmployeeController)
- **Models**: Singular, maiúscula (Employee)
- **Tabelas**: Plural, minúscula (employees)
- **Migrations**: Descritivo do que faz (create_employees_table)
- **Repositories**: Singular, sufixo Repository (EmployeeRepository)
- **Services**: Singular, sufixo Service (EmployeeService)
- **Interfaces**: Prefixo I ou sufixo Interface (IEmployeeRepository ou EmployeeRepositoryInterface)
- **Jobs**: Verbo descritivo (ProcessEmployeeCsvFile)
- **Events**: Verbo no passado (EmployeeUpdated)
- **Listeners**: Ação a ser executada (SendEmployeeUpdateNotification)

## Testes

- Nomear testes de forma descritiva (test_it_validates_email_format)
- Utilizar AAA (Arrange, Act, Assert) ou Given-When-Then
- Escrever testes independentes
- Focar em comportamento, não em implementação
- Testar casos de sucesso e casos de erro
- Manter testes rápidos