#!/bin/bash

# Script para corrigir permissões do Laravel no Docker

echo "🔧 Corrigindo permissões dos diretórios de storage do Laravel..."

# Diretórios que precisam de permissão de escrita
DIRS=(
  "/var/www/html/storage"
  "/var/www/html/storage/app"
  "/var/www/html/storage/app/public"
  "/var/www/html/storage/framework"
  "/var/www/html/storage/framework/cache"
  "/var/www/html/storage/framework/sessions"
  "/var/www/html/storage/framework/views"
  "/var/www/html/storage/logs"
  "/var/www/html/bootstrap/cache"
)

# Garantir que os diretórios existam
for DIR in "${DIRS[@]}"; do
  docker-compose exec app mkdir -p "$DIR"
done

# Ajustar proprietário para www-data (usuário do servidor web)
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache

# Ajustar permissões (777 é mais permissivo, mas garante funcionamento)
docker-compose exec app chmod -R 777 /var/www/html/storage
docker-compose exec app chmod -R 777 /var/www/html/bootstrap/cache

# Limpar cache do Laravel
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan config:clear

echo "✅ Permissões corrigidas com sucesso!"
echo "Tente acessar http://localhost novamente"
