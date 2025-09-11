# CSV Import API - Usage Example

## 1. Authentication
First, get a token (this endpoint is already working):
```bash
curl -X GET http://localhost/api/user
```

## 2. Upload CSV File
```bash
curl -X POST \
  http://localhost/api/employees \
  -H "Authorization: Bearer {your_token}" \
  -H "Content-Type: multipart/form-data" \
  -F "employees=@employees.csv"
```

Response:
```json
{
  "message": "Arquivo enviado com sucesso e será processado em breve",
  "job_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

## 3. Check Import Status
```bash
curl -X GET \
  http://localhost/api/import-status/550e8400-e29b-41d4-a716-446655440000 \
  -H "Authorization: Bearer {your_token}"
```

Response:
```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "message": "Job completed",
  "processed_records": 50,
  "successful_records": 48,
  "failed_records": 2,
  "errors": []
}
```

## 4. List Employees
```bash
curl -X GET \
  http://localhost/api/employees \
  -H "Authorization: Bearer {your_token}"
```

## CSV File Format
Your CSV file should have the following structure:
```csv
name,email,document,city,state,start_date
João Silva,joao@empresa.com,11144477735,São Paulo,SP,2024-01-15
Maria Santos,maria@empresa.com,98765432100,Rio de Janeiro,RJ,2024-01-16
```

## Features
- ✅ Asynchronous processing via Laravel Queues
- ✅ Employee updates by CPF (document field)
- ✅ Email notifications for updates
- ✅ Comprehensive validation (CPF, email, dates)
- ✅ Error handling and logging
- ✅ Authentication via Laravel Passport
- ✅ Job status tracking