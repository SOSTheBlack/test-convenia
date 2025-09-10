#!/bin/bash

# Script para corrigir permissões dentro do container Docker
# Deve ser executado pelo usuário host para garantir acesso aos arquivos

echo "🔧 Corrigindo permissões do projeto para desenvolvimento com Docker..."

# Obter o ID do usuário e grupo atual
USER_ID=$(id -u)
GROUP_ID=$(id -g)
USERNAME=$(whoami)

# Informações
echo "👤 Ajustando permissões para: $USERNAME (UID: $USER_ID, GID: $GROUP_ID)"

# Adicionar variáveis de ambiente ao .env para uso no container
if [ -f .env ]; then
    if ! grep -q "HOST_UID" .env; then
        echo "# User ID for permissions" >> .env
        echo "HOST_UID=$USER_ID" >> .env
        echo "HOST_GID=$GROUP_ID" >> .env
    else
        # Atualizar valores existentes
        sed -i "s/HOST_UID=.*/HOST_UID=$USER_ID/" .env
        sed -i "s/HOST_GID=.*/HOST_GID=$GROUP_ID/" .env
    fi
    echo "✅ Variáveis HOST_UID e HOST_GID configuradas no .env"
else
    echo "⚠️ Arquivo .env não encontrado"
fi

# Verificar se o Docker está rodando
if ! docker-compose ps | grep -q "Up"; then
  echo "⚠️ Os containers Docker não estão rodando. Por favor, inicie-os com 'docker-compose up -d'"
  exit 1
fi

# Aplicar permissões dentro do container
echo "🔄 Aplicando permissões dentro do container..."
docker-compose exec app bash -c "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database"
docker-compose exec app bash -c "chmod -R 775 /var/www/html"
docker-compose exec app bash -c "chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache"

# Executar comandos de limpeza de cache
echo "🧹 Limpando cache do Laravel..."
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear

echo "✅ Permissões corrigidas com sucesso!"
echo "🌟 O projeto deve funcionar corretamente agora!"
