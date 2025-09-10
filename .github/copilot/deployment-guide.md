# Guia de Deployment e Ambiente Local

## Ambiente Local com Docker

Este projeto está configurado para rodar facilmente em ambiente local usando Docker e Docker Compose, garantindo consistência entre os ambientes de desenvolvimento e produção.

### Requisitos

- Docker
- Docker Compose
- Git

### Configuração Inicial

1. Clone o repositório:

```bash
git clone https://github.com/seu-usuario/test-convenia.git
cd test-convenia
```

2. Copie o arquivo de ambiente:

```bash
cp .env.example .env
```

3. Configure as variáveis de ambiente no arquivo `.env`:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=convenia
DB_USERNAME=convenia
DB_PASSWORD=senha_segura

QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=no-reply@convenia.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Iniciar o Ambiente

1. Inicie os containers Docker:

```bash
docker-compose up -d
```

2. Acesse o container da aplicação:

```bash
docker-compose exec app bash
```

3. Instale as dependências do Composer:

```bash
composer install
```

4. Gere a chave da aplicação:

```bash
php artisan key:generate
```

5. Execute as migrações:

```bash
php artisan migrate
```

6. Configure o Passport:

```bash
php artisan passport:install
```

### Iniciar Workers para Filas

Para processamento assíncrono de filas:

```bash
docker-compose exec app php artisan queue:work --queue=csv-processing
```

Para ambiente de desenvolvimento, você pode usar:

```bash
docker-compose exec app php artisan queue:listen --queue=csv-processing
```

### Testes

Execute os testes dentro do container:

```bash
docker-compose exec app php artisan test
```

Para testes com cobertura:

```bash
docker-compose exec app php artisan test --coverage-html reports/
```

### Verificação de Qualidade de Código

```bash
# Análise estática com Larastan
docker-compose exec app ./vendor/bin/phpstan analyse

# Verificação de estilo com PHP_CodeSniffer
docker-compose exec app ./vendor/bin/phpcs

# Detecção de problemas com PHP Mess Detector
docker-compose exec app ./vendor/bin/phpmd app text phpmd.xml
```

## Deployment em Produção

### Requisitos de Servidor

- PHP 8.4+
- Composer
- MySQL 8.0+ ou PostgreSQL 13+
- Redis (para filas)
- Servidor Web (Nginx ou Apache)

### Processo de Deployment

1. Clone o repositório no servidor:

```bash
git clone https://github.com/seu-usuario/test-convenia.git
cd test-convenia
```

2. Instale dependências de produção:

```bash
composer install --no-dev --optimize-autoloader
```

3. Configure o ambiente:

```bash
cp .env.example .env
# Edite o .env com as configurações de produção
php artisan key:generate
```

4. Execute migrações:

```bash
php artisan migrate --force
```

5. Configure o Passport:

```bash
php artisan passport:install
```

6. Otimize a aplicação:

```bash
php artisan optimize
php artisan route:cache
php artisan config:cache
php artisan view:cache
```

7. Configure o Supervisor para gerenciar as filas:

```
[program:convenia-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /caminho/para/projeto/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --queue=csv-processing
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/caminho/para/logs/worker.log
stopwaitsecs=3600
```

8. Configure o servidor web (exemplo para Nginx):

```nginx
server {
    listen 80;
    server_name api.seudominio.com;
    root /caminho/para/projeto/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Configuração de SSL

Recomenda-se configurar SSL com Let's Encrypt:

```bash
certbot --nginx -d api.seudominio.com
```

### Backup e Disaster Recovery

1. Configure backups diários do banco de dados:

```bash
# Exemplo de script para backup diário
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d")
BACKUP_DIR="/backups/database"
DB_USER="usuario_db"
DB_PASS="senha_db"
DB_NAME="convenia"

mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/$DB_NAME-$TIMESTAMP.sql
gzip $BACKUP_DIR/$DB_NAME-$TIMESTAMP.sql
find $BACKUP_DIR -type f -name "*.gz" -mtime +7 -exec rm {} \;
```

2. Configure backup do código-fonte:

```bash
# Exemplo de script para backup do código
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d")
BACKUP_DIR="/backups/code"
SOURCE_DIR="/caminho/para/projeto"

tar -czf $BACKUP_DIR/convenia-$TIMESTAMP.tar.gz $SOURCE_DIR
find $BACKUP_DIR -type f -name "*.tar.gz" -mtime +7 -exec rm {} \;
```

### Monitoramento

- Configure o Laravel Horizon para monitoramento de filas
- Implemente logs estruturados com ELK Stack ou similar
- Configure alertas para erros críticos