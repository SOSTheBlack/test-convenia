# Modelos e Relacionamentos

## Visão Geral

Este documento descreve os principais modelos de dados do sistema e seus relacionamentos. Seguimos as convenções do Eloquent ORM para definir modelos claros e relacionamentos expressivos.

## Modelos Principais

### User

Representa os usuários do sistema que farão upload de arquivos CSV e estarão relacionados aos funcionários importados.

```php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
```

### Employee

Representa os funcionários importados via CSV.

```php
class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'document',
        'phone',
        'birth_date',
        'salary',
        'department',
        'user_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'salary' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### ImportJob

Rastreia os jobs de importação para permitir consulta de status.

```php
class ImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'status',
        'file_name',
        'total_records',
        'processed_records',
        'successful_records',
        'failed_records',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function errors()
    {
        return $this->hasMany(ImportError::class);
    }
}
```

### ImportError

Armazena erros ocorridos durante a importação para relatórios detalhados.

```php
class ImportError extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_job_id',
        'row',
        'column',
        'message',
    ];

    public function importJob()
    {
        return $this->belongsTo(ImportJob::class);
    }
}
```

## Migrações

### Users Table

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
```

### Employees Table

```php
Schema::create('employees', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email');
    $table->string('document', 20)->unique();
    $table->string('phone')->nullable();
    $table->date('birth_date');
    $table->decimal('salary', 10, 2);
    $table->string('department');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamps();
    
    // Índices para otimização de consultas
    $table->index('document');
    $table->index('department');
});
```

### Import Jobs Table

```php
Schema::create('import_jobs', function (Blueprint $table) {
    $table->id();
    $table->uuid('job_id')->unique();
    $table->string('status'); // pending, processing, completed, failed
    $table->string('file_name');
    $table->integer('total_records')->default(0);
    $table->integer('processed_records')->default(0);
    $table->integer('successful_records')->default(0);
    $table->integer('failed_records')->default(0);
    $table->foreignId('user_id')->constrained();
    $table->timestamps();
    
    $table->index('job_id');
    $table->index('status');
});
```

### Import Errors Table

```php
Schema::create('import_errors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('import_job_id')->constrained()->onDelete('cascade');
    $table->integer('row');
    $table->string('column')->nullable();
    $table->string('message');
    $table->timestamps();
    
    $table->index('import_job_id');
});
```

## Relacionamentos

```
┌─────────┐       ┌───────────┐       ┌────────────┐       ┌─────────────┐
│  User   │───┐   │  Employee │       │ ImportJob  │───┐   │ ImportError │
└─────────┘   │   └───────────┘       └────────────┘   │   └─────────────┘
              │         ▲              ▲              │           ▲
              │         │              │              │           │
              └─────────┘              └──────────────┘───────────┘
```

- Um User pode ter muitos Employees (1:N)
- Um User pode ter muitos ImportJobs (1:N)
- Um ImportJob pertence a um User (N:1)
- Um ImportJob pode ter muitos ImportErrors (1:N)
- Um ImportError pertence a um ImportJob (N:1)

## Boas Práticas para Modelos

1. **Encapsular Lógica de Negócios**: Colocar métodos relacionados à lógica de negócios específica do modelo dentro da própria classe modelo.

2. **Acessores e Mutadores**: Usar para formatar dados consistentemente:

```php
// No modelo Employee
public function getFormattedSalaryAttribute(): string
{
    return 'R$ ' . number_format($this->salary, 2, ',', '.');
}

public function setDocumentAttribute($value): void
{
    $this->attributes['document'] = preg_replace('/[^0-9]/', '', $value);
}
```

3. **Escopos**: Criar escopos para consultas comuns:

```php
// No modelo Employee
public function scopeByDepartment($query, string $department)
{
    return $query->where('department', $department);
}

public function scopeActive($query)
{
    return $query->where('active', true);
}
```

4. **Eventos do Modelo**: Utilizar eventos do Eloquent para ações automáticas:

```php
// No modelo Employee
protected static function booted()
{
    static::created(function ($employee) {
        event(new EmployeeCreated($employee));
    });
    
    static::updated(function ($employee) {
        event(new EmployeeUpdated($employee));
    });
}
```

5. **Validação**: Implementar métodos de validação nos próprios modelos:

```php
// No modelo Employee
public static function validateCSVRow(array $row): array
{
    $errors = [];
    
    if (empty($row['name'])) {
        $errors['name'] = 'O nome é obrigatório.';
    }
    
    if (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'O e-mail é inválido.';
    }
    
    // Mais validações...
    
    return $errors;
}
```