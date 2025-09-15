#!/bin/bash

# Script para rodar testes no container Docker

echo "🧪 Executando testes no ambiente Docker..."

# Garantir que o container está rodando
docker-compose up -d

# Aguardar container ficar pronto
sleep 5

# Limpar cache antes dos testes
echo "🧹 Limpando cache..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear

# Preparar banco de testes
echo "🗃️ Preparando banco para testes..."
docker-compose exec app php artisan migrate:fresh --seed --env=testing

# Executar testes
echo "🚀 Executando testes..."
docker-compose exec app php artisan test

# Executar testes com PHPUnit (alternativo)
echo "🔬 Executando PHPUnit..."
docker-compose exec app vendor/bin/phpunit

echo "✅ Testes concluídos!"
