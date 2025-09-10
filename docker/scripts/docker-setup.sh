#!/bin/bash

# Script de setup do ambiente Docker para Laravel

echo "🚀 Configurando o ambiente Laravel com Docker v1..."

# Verificar se o Docker está instalado
if ! command -v docker >/dev/null 2>&1 || ! command -v docker-compose >/dev/null 2>&1; then
    echo "❌ Docker e/ou docker-compose não estão instalados. Por favor, instale-os primeiro."
    exit 1
fi

# Verificar se o .env.docker existe
if [ ! -f .env.docker ]; then
    echo "❌ Arquivo .env.docker não encontrado."
    exit 1
fi

# Copiar arquivo .env
if [ ! -f .env ]; then
    echo "📋 Copiando arquivo .env..."
    cp .env.docker .env
fi

# Obter IDs do usuário atual
USER_ID=$(id -u)
GROUP_ID=$(id -g)
echo "🔍 Usando UID:GID do usuário atual: $USER_ID:$GROUP_ID"

# Atualizar .env.docker com os IDs do usuário atual
sed -i "s/USER_ID=.*/USER_ID=$USER_ID/" .env.docker
sed -i "s/GROUP_ID=.*/GROUP_ID=$GROUP_ID/" .env.docker
echo "✅ Variáveis USER_ID e GROUP_ID atualizadas no .env.docker"

# Verificar se precisamos reconstruir os containers
echo "🔄 Verificando se os containers precisam ser reconstruídos..."
if docker-compose ps | grep -q "laravel-engineer"; then
    echo "🧹 Removendo containers existentes para reconstrução limpa..."
    docker-compose down
fi

# Construir os containers
echo "🏗️  Construindo containers Docker com as permissões adequadas..."
USER_ID=$USER_ID GROUP_ID=$GROUP_ID docker-compose build --no-cache

# Iniciar os containers
echo "🚀 Iniciando containers Docker..."
USER_ID=$USER_ID GROUP_ID=$GROUP_ID docker-compose up -d

# Verificar se os containers estão rodando
if ! docker-compose ps | grep -q "Up"; then
    echo "❌ Falha ao iniciar os containers. Verifique os logs com 'docker-compose logs'"
    exit 1
fi

# Criar diretórios de cache se necessário e ajustar permissões
echo "🔧 Configurando diretórios de cache e storage..."
docker-compose exec app bash -c "
    mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
    chown -R www-data:devgroup storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
"

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
docker-compose exec app php artisan migrate:fresh

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
