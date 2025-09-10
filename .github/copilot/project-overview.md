# Visão Geral do Projeto

## Sobre o Desafio

O desafio da Convenia consiste em desenvolver uma API em Laravel para importação de funcionários através de arquivos CSV. A solução deve ser robusta, escalável e seguir as melhores práticas de desenvolvimento.

## Requisitos Principais

- Endpoint `POST /api/employees` para receber uploads de arquivos CSV
- Processamento assíncrono dos arquivos (usando Jobs e Queues)
- Atualização de funcionários existentes (identificados pelo campo `document`)
- Validação completa dos dados
- Relacionamento de funcionários com usuários autenticados
- Notificações por e-mail sobre atualizações
- Autenticação via JWT/Passport
- Testes unitários e de feature

## Critérios de Avaliação

- Qualidade e organização do código
- Aplicação de padrões de design (Services, Repositories, DTOs)
- Tratamento adequado de erros
- Documentação clara
- Cobertura de testes
- Escalabilidade da solução

## Funcionalidades Principais

1. **Upload e processamento de CSV** - Receber arquivos CSV com dados de funcionários
2. **Validação de dados** - Garantir integridade dos dados recebidos
3. **Persistência de dados** - Salvar/atualizar funcionários no banco
4. **Notificação** - Enviar e-mails sobre atualizações realizadas
5. **Autenticação e autorização** - Proteger endpoints e vincular funcionários ao usuário autenticado

## Tecnologias

- PHP 8.4
- Laravel 12
- MySQL/PostgreSQL
- Docker e Docker Compose
- PHPUnit para testes
- Larastan e PHPMD para qualidade de código