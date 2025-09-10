# Convenia - Backend Challenge

Bem-vindo ao desafio técnico Convenia!  
Este projeto consiste em implementar uma API em Laravel para importar funcionários via upload de arquivo CSV, garantindo atualização eficiente dos dados e notificações por email.

---

## Requisitos Gerais

- Utilize **PHP/Laravel** seguindo as melhores práticas e padrões de arquitetura modernos.
- O upload de funcionários deve ser feito via endpoint `POST /api/employees`, aceitando arquivos CSV.
- Atualize os dados dos funcionários já existentes (identificados pelo campo `document`), sem duplicidade.
- Relacione cada funcionário ao usuário autenticado (via token).
- Envie um email ao usuário sobre cada atualização realizada.
- **Processamento assíncrono e escalável**: O arquivo CSV pode ser grande; utilize Jobs, Queues, ou eventos para processar os dados fora da requisição principal.
- Valide todos os dados do CSV (incluindo datas, emails, documentos).
- Siga as regras de autorização (token JWT/Passport).

---

## Boas Práticas e Requisitos Técnicos

- Implemente **testes unitários e de feature** para toda a lógica de importação e atualização.
- Adote padrões como **Services**, **Repositories** e **Jobs** para desacoplamento e escalabilidade.
- Utilize **DTOs** para transporte seguro de dados.
- Organize o código seguindo o padrão PSR12 e mantenha a cobertura de testes alta.
- Utilize Larastan e PHPMD para garantir qualidade e estilo.
- Trate erros de forma clara e consistente (ex: resposta 406 para datas inválidas).
- Documente endpoints e exemplos de uso (README ou comentários).
- Escreva commits claros e descritivos.

---

## Como rodar localmente

- O ambiente está preparado para rodar com Docker (PHP 8.4, Laravel 12).  
- Use `docker-compose up -d` para subir o ambiente.
- Execute comandos do Laravel como `composer install`, `php artisan migrate`, `passport:install`, e `phpunit` dentro do container.

---

## O que esperamos de você

- **Código limpo, organizado e testado**.
- Soluções escaláveis, desacopladas e fáceis de manter.
- Atenção à performance e segurança.
- Comunicação clara em código, testes e documentação.
- Proatividade em melhorar o projeto e sugerir boas práticas.

Boa sorte!  