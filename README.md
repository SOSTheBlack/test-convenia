# Convenia


## Avaliação Técnica - Backend

Bem-vindo ao desafio técnico Convenia! Este projeto é uma API para manipulação de colaboradores de uma empresa, já com autenticação, models e rotas básicas implementadas. Seu objetivo é completar a funcionalidade de carga de colaboradores via CSV.

Que tenha um excelente desenvolvimento! :wink:

### Desafio

> Esta é uma aplicação quase completa para a manipulação de colaboradores via API. Já temos a autenticação, alguns `Models` e rotas para visualizar e excluir colaboradores.
> O desafio consiste em configurar esta aplicação e adicionar o envio de um arquivo CSV para criar os colaboradores no banco de dados e atualizar seus dados caso sejam submetidos novamente com novos dados.
> Faça um Fork deste repositório. Avaliaremos cuidadosamente o seu Merge Request, então capriche no seu desenvolvimento. Observaremos uso de boas práticas, testes e seus commits.


---

## 🚀 Instalação e Configuração

Siga os passos abaixo para rodar o projeto localmente:

```bash
# Clone o repositório
git clone <url-do-repo>
cd laravel-engineer

# Copie o arquivo de ambiente
cp .env.example .env

# Crie o banco SQLite
touch database/database.sqlite

# Instale as dependências
composer install

# Gere a chave da aplicação
php artisan key:generate

# Execute as migrações
php artisan migrate

# Instale o Passport (cria clientes OAuth)
php artisan passport:install --force

# Cria usuários de teste, e gera token.
php artisan db:seed

# Execute os testes
php vendor/bin/phpunit

# Inicie o servidor local
php artisan serve
```

---

## 📦 Versões Utilizadas

| Componente           | Versão         |
|----------------------|---------------|
| Laravel Framework    | 12.x          |
| PHP                  | 8.4           |
| Laravel Passport     | 12.x          |
| PHPUnit              | 11.x          |
| GuzzleHTTP           | 7.x           |
| Laravel Sail         | 1.x           |
| Faker                | 1.x           |
| Mockery              | 1.x           |
| Collision            | 8.x           |
| Laravel Pint         | 1.x           |

---

## ✅ Testes

Todos os testes automatizados estão passando:

```
PHPUnit 11.x by Sebastian Bergmann and contributors.
Runtime: PHP 8.4.1

........                                                            8 / 8 (100%)

Tests: 8, Assertions: 18, PHPUnit Deprecations: 1.
OK! ✅
```

---


### User Story

```gherkin
#language:pt
Funcionalidade: API de Colaboradores
Como Usuário do sistema de Colaboradores
Gostaria de contar com uma API para manipular algumas informações de meus colaboradores
De maneira que estas informações alimentem meu sistema pessoal

    Cenário: Requisição não autorizada
        Dado que tenhamos a API disponível
        Quando requisitarmos qualquer rota sem um token
        Então a resposta deve ser 401 Unauthorized

    Cenário: Requisição com token inválido
        Dado que tenhamos a API disponível
        Quando requisitarmos qualquer rota com um token inválido
        Então a resposta deve ser 401 Unauthorized

    Contexto: Requisições autenticadas
        Dado que tenhamos a API disponível
        E tenhamos o token adquirido pela rota "/api/user"
        E que as requisições a seguir terão um token válido

    Cenário: Upload com sucesso de CSV
        Dado que tenhamos um arquivo .csv
        E este arquivo contenha os seguintes dados:
          |name             |email              |document    |city     |state |start_date |
          |Bob Wilson       |bob@paopaocafe.com |13001647000 |Salvador |BA    |2020-01-15 |
          |Laura Matsuda    |lm@matsuda.com.br  |60095284028 |Niterói  |RJ    |2019-06-08 |
          |Marco Rodrigues  |marco@kyokugen.org |71306511054 |Osasco   |SC    |2021-01-10 |
          |Christie Monteiro|monteiro@namco.com |28586454001 |Recife   |PE    |2015-11-03 |
        Quando o submetermos para o endpoint "/api/employees" com o método POST
        Então devemos ter como status de resposta "200"
        E os novos colaboradores devem constar no banco de dados
        E devem estar relacionados com usuário do token
        E o usuário do token deve receber um email sobre essa atualização

    Cenário: Upload de atualização com sucesso de CSV
        Dado que já tenhamos populado a nossa base de dados
        E tenhamos um arquivo .csv com os seguintes dados:
            |name             |email              |document    |city     |state |start_date |
            |Marco Rodrigues  |marco@kyokugen.org |71306511054 |Osasco   |SP    |2021-01-10 |
        Quando o submetermos para o endpoint "/api/employees" com o método POST
        Então devemos ter como status de resposta "200"
        E o colaborador "Marco Rodrigues" deve ter "SP" no campo "state"
        E deve estar relacionado com usuário do token
        E o usuário do token deve receber um email sobre essa atualização

    Cenário: Upload com erro
        Dado que já tenhamos populado a nossa base de dados
        Quando submetermos um CSV considerando os seguintes dados:
            |name             |email              |document    |city     |state |start_date |
            |Jimmy Blanka     |blanka@enel.com.br |33010323034 |Manaus   |AM    |2021-02-29 |
        Então devemos ter como status de resposta 406
        E a resposta deve ser "NOT ACCEPTABLE: Invalid Date"
        E nada deve ser alterado na base de dados
```


---

### Considerações Finais

* O projeto está pronto para rodar com PHP 8.4 e Laravel 12
* Todos os testes automatizados estão passando
* Utilize boas práticas de desenvolvimento, commit e testes
* Um arquivo CSV pode ter 2 ou 2.000.000 de linhas. Considere processar arquivos grandes fora da requisição HTTP da API (`#FIKDIK`)
* O código será avaliado por estilo (PSR12), PHPMD, Larastan, cobertura e qualidade dos testes

Inclua seu nome e email para contato na mensagem do Merge Request para que possamos entrar em contato.

---

Convenia :purple_heart:
