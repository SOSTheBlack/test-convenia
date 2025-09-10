# Documentação da API

## Autenticação

Todos os endpoints requerem autenticação via Bearer Token (OAuth 2.0 / Passport).

### Obter Token

```
POST /api/auth/login
```

**Request Body:**
```json
{
    "email": "usuario@exemplo.com",
    "password": "senha_segura"
}
```

**Response (200 OK):**
```json
{
    "token_type": "Bearer",
    "expires_in": 31536000,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1..."
}
```

## Endpoints de Funcionários

### Importar Funcionários (CSV)

```
POST /api/employees
```

**Headers:**
```
Content-Type: multipart/form-data
Authorization: Bearer {seu_token}
```

**Request Body:**
```
file: [Arquivo CSV]
```

**Response (202 Accepted):**
```json
{
    "message": "Arquivo enviado com sucesso e será processado em breve",
    "job_id": "uuidv4-job-identifier"
}
```

**Formato esperado do CSV:**
```csv
name,email,document,phone,birth_date,salary,department
"João Silva","joao@empresa.com","12345678900","11999998888","1985-10-15","5000.00","TI"
"Maria Santos","maria@empresa.com","98765432100","11999997777","1990-05-20","6000.00","RH"
```

### Verificar Status da Importação

```
GET /api/import-status/{job_id}
```

**Headers:**
```
Authorization: Bearer {seu_token}
```

**Response (200 OK):**
```json
{
    "status": "completed",
    "processed_records": 150,
    "successful_records": 148,
    "failed_records": 2,
    "errors": [
        {
            "line": 45,
            "message": "E-mail inválido"
        },
        {
            "line": 87,
            "message": "Data de nascimento em formato incorreto"
        }
    ]
}
```

### Listar Funcionários

```
GET /api/employees
```

**Headers:**
```
Authorization: Bearer {seu_token}
```

**Query Parameters:**
```
page: número da página (default: 1)
per_page: itens por página (default: 15)
department: filtrar por departamento (opcional)
```

**Response (200 OK):**
```json
{
    "data": [
        {
            "id": 1,
            "name": "João Silva",
            "email": "joao@empresa.com",
            "document": "12345678900",
            "phone": "11999998888",
            "birth_date": "1985-10-15",
            "salary": "5000.00",
            "department": "TI",
            "created_at": "2023-10-01T14:30:00Z",
            "updated_at": "2023-10-01T14:30:00Z"
        },
        // ... mais funcionários
    ],
    "links": {
        "first": "http://localhost/api/employees?page=1",
        "last": "http://localhost/api/employees?page=10",
        "prev": null,
        "next": "http://localhost/api/employees?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "path": "http://localhost/api/employees",
        "per_page": 15,
        "to": 15,
        "total": 150
    }
}
```

## Códigos de Erro

- `400` - Bad Request: Dados inválidos ou faltantes
- `401` - Unauthorized: Token inválido ou expirado
- `403` - Forbidden: Sem permissão para acessar o recurso
- `404` - Not Found: Recurso não encontrado
- `406` - Not Acceptable: Dados com formato inválido
- `422` - Unprocessable Entity: Validação falhou
- `500` - Internal Server Error: Erro não tratado no servidor

## Exemplo de Tratamento de Erros

**Response (422 Unprocessable Entity):**
```json
{
    "message": "Os dados fornecidos são inválidos.",
    "errors": {
        "file": [
            "O arquivo CSV é obrigatório.",
            "O arquivo deve estar no formato CSV."
        ]
    }
}
```