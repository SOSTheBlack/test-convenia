# Ambiente Docker para Laravel 12 com PHP 8.4

Este documento descreve como configurar e usar o ambiente Docker para desenvolvimento do projeto Laravel com as versões mais atuais.

## 📋 Requisitos

- Docker
- Docker Compose
- Git

## 🏗️ Arquitetura

O ambiente Docker é composto pelos seguintes serviços:

- **app**: Container principal com PHP 8.4, Nginx, e todas as extensões necessárias
- **worker**: Container para processar jobs/queues do Laravel
- **redis**: Cache e gerenciamento de sessões
- **mailhog**: Servidor de email para testes locais

## 🚀 Setup Inicial

### 1. Setup Automático (Recomendado)

Execute o script de setup que configura tudo automaticamente:

```bash
./docker-run setup
```

### 2. Setup Manual

Se preferir fazer manualmente:

```bash
# 1. Copiar arquivo de ambiente
cp .env.docker .env

# 2. Construir containers
docker-compose build

# 3. Iniciar serviços
docker-compose up -d

# 4. Instalar dependências
docker-compose exec app composer install

# 5. Gerar chave da aplicação
docker-compose exec app php artisan key:generate

# 6. Preparar banco de dados
docker-compose exec app php artisan migrate
docker-compose exec app php artisan passport:install
docker-compose exec app php artisan db:seed
```

## 🔧 Comandos Úteis

### Gerenciamento de Containers

```bash
# Iniciar todos os serviços
docker-compose up -d

# Parar todos os serviços
docker-compose down

# Ver logs de um serviço
docker-compose logs app
docker-compose logs worker

# Reconstruir containers
docker-compose build --no-cache

# Acessar shell do container principal
docker-compose exec app bash
```

### Laravel/Artisan

```bash
# Comandos artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan tinker
docker-compose exec app php artisan queue:work
docker-compose exec app php artisan config:clear

# Rodar testes
docker-compose exec app php artisan test
docker-compose exec app vendor/bin/phpunit
```

### Composer

```bash
# Instalar dependências
docker-compose exec app composer install

# Adicionar pacote
docker-compose exec app composer require nome/pacote

# Atualizar dependências
docker-compose exec app composer update
```

### Permissões

```bash
# Corrigir permissões (se necessário)
docker-compose exec app chown -R www-data:www-data /var/www/html
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

## 🌐 Acessos

- **Aplicação Laravel**: http://localhost
- **MailHog (emails)**: http://localhost:8025
- **Redis**: localhost:6379

## 📁 Estrutura de Arquivos Docker

```
docker/
├── nginx/
│   ├── nginx.conf          # Configuração principal do Nginx
│   └── laravel.conf        # Virtual host para Laravel
├── php/
│   └── local.ini           # Configurações customizadas do PHP
├── scripts/
│   ├── docker-setup.sh     # Script de configuração inicial
│   ├── docker-test.sh      # Script para executar testes
│   ├── fix-git-ownership.sh  # Correção para problemas do Git
│   ├── fix-permissions.sh  # Correção para problemas de permissão
│   └── restart-docker.sh   # Script para reconstrução de containers
└── supervisor/
    └── supervisord.conf    # Configuração do Supervisor
```

## ⚙️ Configurações

### PHP 8.4

O container inclui as seguintes extensões PHP:
- pdo_sqlite, pdo_mysql
- mbstring, exif, pcntl
- bcmath, gd, xml
- Opcache configurado para desenvolvimento

### Nginx

- Configurado para servir arquivos estáticos
- Compressão gzip habilitada
- Headers de segurança configurados
- Upload máximo: 40MB

### SQLite

- Banco de dados local em `database/database.sqlite`
- Permissões configuradas automaticamente
- Backup recomendado antes de mudanças estruturais

## 🔄 Workflows de Desenvolvimento

### Desenvolvimento Diário

1. Iniciar ambiente: `docker-compose up -d`
2. Desenvolver normalmente (arquivos são sincronizados via volume)
3. Rodar testes: `docker-compose exec app php artisan test`
4. Ver logs se necessário: `docker-compose logs app`

### Deploy/Atualizações

1. Parar ambiente: `docker-compose down`
2. Atualizar código (git pull, etc.)
3. Reconstruir se necessário: `docker-compose build`
4. Iniciar: `docker-compose up -d`
5. Rodar migrations: `docker-compose exec app php artisan migrate`

### Debugging

```bash
# Ver logs em tempo real
docker-compose logs -f app

# Acessar container para debugging
docker-compose exec app bash

# Verificar status dos serviços
docker-compose ps

# Verificar recursos utilizados
docker stats
```

## 🧪 Testes

### Executar Testes

```bash
# Todos os testes
docker-compose exec app php artisan test

# Testes específicos
docker-compose exec app php artisan test --filter=TestName

# Com coverage (se configurado)
docker-compose exec app vendor/bin/phpunit --coverage-html coverage
```

### Banco de Testes

O ambiente usa SQLite por padrão, que é ideal para testes por ser rápido e isolado.

## 🚨 Troubleshooting

### Problemas Comuns

1. **Permissões de arquivo**:
   ```bash
   docker-compose exec app chown -R www-data:www-data /var/www/html
   ```

2. **Cache problems**:
   ```bash
   docker-compose exec app php artisan config:clear
   docker-compose exec app php artisan cache:clear
   ```

3. **Container não inicia**:
   ```bash
   docker-compose logs app
   docker-compose down && docker-compose up -d
   ```

4. **Porta 80 ocupada**:
   Edite `docker-compose.yml` e mude a porta:
   ```yaml
   ports:
     - "8080:80"  # Usar porta 8080 no host
   ```

### Logs Importantes

- Nginx: `/var/log/nginx/laravel_error.log`
- PHP-FPM: `/var/log/php-fpm.log`
- Laravel: `storage/logs/laravel.log`

## 🔐 Segurança

### Desenvolvimento

- Nunca usar em produção sem ajustes de segurança
- Arquivo `.env` contém configurações de desenvolvimento
- Debug habilitado por padrão

### Recomendações

- Mantenha dependências atualizadas
- Use volumes específicos para dados sensíveis
- Configure HTTPS para produção

## 📈 Performance

### Otimizações Incluídas

- Opcache habilitado
- Nginx com compressão gzip
- Cache de configuração do Laravel
- Volumes delegados para melhor performance no macOS

### Monitoramento

```bash
# Ver uso de recursos
docker stats

# Ver logs de performance
docker-compose logs app | grep -i slow
```

## 🤝 Contribuindo

Para contribuir com melhorias no ambiente Docker:

1. Teste suas mudanças localmente
2. Documente alterações significativas
3. Mantenha compatibilidade com versões atuais

## 📚 Referências

- [Docker Compose](https://docs.docker.com/compose/)
- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [PHP 8.4 Features](https://www.php.net/releases/8.4/en.php)
- [Nginx Configuration](https://nginx.org/en/docs/)
