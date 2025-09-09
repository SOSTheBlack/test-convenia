#!/bin/bash

# Script de setup do ambiente Docker para Laravel

echo "🚀 Configurando o ambiente Laravel com Docker..."

# Copiar arquivo .env
if [ ! -f .env ]; then
    echo "📋 Copiando arquivo .env..."
    cp .env.docker .env
fi

# Build dos containers
echo "🔨 Construindo os containers Docker..."
docker-compose build --no-cache

# Subir os containers
echo "▶️ Iniciando os containers..."
docker-compose up -d

# Aguardar containers ficarem prontos
echo "⏳ Aguardando containers ficarem prontos..."
sleep 10

# Instalar dependências
echo "📦 Instalando dependências do Composer..."
docker-compose exec app composer install

# Gerar chave da aplicação
echo "🔑 Gerando chave da aplicação..."
docker-compose exec app php artisan key:generate

# Criar e preparar banco de dados
echo "🗃️ Criando banco de dados SQLite..."
docker-compose exec app touch database/database.sqlite
docker-compose exec app chmod 664 database/database.sqlite

# Rodar migrations
echo "🏗️ Executando migrations..."
docker-compose exec app php artisan migrate

# Instalar Passport
echo "🔐 Instalando Laravel Passport..."
docker-compose exec app php artisan passport:install

# Rodar seeders
echo "🌱 Executando seeders..."
docker-compose exec app php artisan db:seed

# Limpar e otimizar cache
echo "🧹 Limpando cache..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Definir permissões
echo "🔧 Ajustando permissões..."
docker-compose exec app chown -R www-data:www-data /var/www/html
docker-compose exec app chmod -R 755 /var/www/html
docker-compose exec app chmod -R 775 /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/bootstrap/cache

echo ""
echo "✅ Setup concluído!"
echo ""
echo "🌐 Acesse sua aplicação em: http://localhost"
echo "📧 MailHog (teste de emails): http://localhost:8025"
echo ""
echo "📋 Comandos úteis:"
echo "  docker-compose exec app php artisan tinker"
echo "  docker-compose exec app php artisan test"
echo "  docker-compose exec app composer install"
echo "  docker-compose logs app"
echo ""
