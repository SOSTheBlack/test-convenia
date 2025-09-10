#!/bin/bash

# Script para corrigir o erro "dubious ownership" do Git no Docker

echo "🛠️ Corrigindo problema de 'dubious ownership' do Git no Docker..."

# Executar o comando git config no container
docker-compose exec app git config --global --add safe.directory /var/www/html

echo "✅ Configuração concluída!"
echo "Agora você pode rodar 'docker-compose exec app composer install' normalmente"
