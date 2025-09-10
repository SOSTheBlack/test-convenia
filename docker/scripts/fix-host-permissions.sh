#!/bin/bash

# Script para corrigir permissões no host
echo "Corrigindo permissões do diretório da aplicação..."

# Seu usuário atual
USER_ID=$(id -u)
GROUP_ID=$(id -g)

# Corrigir permissões básicas do Laravel
sudo find . -type f -not -path "*/vendor/*" -not -path "*/node_modules/*" -exec chmod 664 {} \;
sudo find . -type d -not -path "*/vendor/*" -not -path "*/node_modules/*" -exec chmod 775 {} \;

# Permissões especiais para diretórios que precisam de escrita
sudo chmod -R 777 storage
sudo chmod -R 777 bootstrap/cache
sudo chmod -R 777 database

# Permissões para os scripts
sudo chmod +x artisan
sudo chmod +x docker-run
sudo find ./docker/scripts -type f -name "*.sh" -exec chmod +x {} \;

echo "Permissões corrigidas!"
