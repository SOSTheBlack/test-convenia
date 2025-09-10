# Processamento de CSV

## Visão Geral do Processamento

O processamento de arquivos CSV é uma operação potencialmente demorada, especialmente para arquivos grandes. Por isso, implementamos um fluxo assíncrono usando jobs e filas do Laravel.

## Fluxo de Processamento

```
┌─────────────┐    ┌─────────────┐    ┌────────────────────┐
│  Controller │───▶│    Job      │───▶│  CSV Import Service │
└─────────────┘    └─────────────┘    └────────────────────┘
                                              │
                         ┌────────────────────┴───────────────────┐
                         ▼                                        ▼
                   ┌──────────┐                            ┌─────────────┐
                   │   DTO    │                            │ Repository  │
                   └──────────┘                            └─────────────┘
                         │                                        │
                         │                                        ▼
                         │                                  ┌──────────┐
                         └────────────────────────────────▶│ Database │
                                                           └──────────┘
```

1. O arquivo CSV é recebido pelo controller
2. O controller valida o formato do arquivo
3. O controller dispara um job para processamento assíncrono
4. O job utiliza um serviço dedicado (CsvImportService) para processar o arquivo
5. O serviço lê o arquivo linha a linha para evitar problemas de memória
6. Cada linha é convertida em um DTO (EmployeeData)
7. O DTO é validado antes de ser processado
8. Os dados validados são enviados para o Repository
9. O Repository cria ou atualiza o registro do funcionário
10. Um evento é disparado para notificar sobre a atualização
11. O status do processamento é atualizado

## Validações

As seguintes validações são aplicadas aos dados do CSV:

| Campo       | Validações                                           |
|-------------|------------------------------------------------------|
| name        | Obrigatório, string, min: 2, max: 100                |
| email       | Obrigatório, e-mail válido, único                    |
| document    | Obrigatório, formato CPF válido, único               |
| phone       | Opcional, formato válido                             |
| birth_date  | Obrigatório, formato Y-m-d, data válida, no passado  |
| salary      | Obrigatório, numérico, maior que 0                   |
| department  | Obrigatório, string, max: 100                        |

## Tratamento de Erros

- Linhas inválidas são registradas em log específico
- O processamento continua mesmo com linhas inválidas
- Um relatório final é gerado com detalhes sobre sucesso/falhas
- Exceções são capturadas e registradas

## Estrutura do Arquivo CSV

O arquivo CSV deve seguir a seguinte estrutura:

```csv
name,email,document,phone,birth_date,salary,department
"Nome Completo","email@dominio.com","12345678900","11999998888","1990-01-01","5000.00","Departamento"
```

Observações:
- A primeira linha deve conter o cabeçalho
- Os campos podem estar entre aspas (recomendado)
- As datas devem estar no formato YYYY-MM-DD
- Os valores decimais devem usar ponto como separador

## Escalabilidade

- Para arquivos muito grandes, considera-se o processamento em chunks
- As filas são configuradas com tentativas múltiplas em caso de falha
- O timeout do job é configurado adequadamente para o tamanho esperado do arquivo
- Monitoramento via Laravel Horizon é recomendado em produção
- Configuração de workers dedicados para processar esta fila específica