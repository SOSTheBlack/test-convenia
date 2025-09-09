#!/bin/bash

# Script para reiniciar os containers Docker após modificações

echo "🔄 Reiniciando containers Docker..."

# Parar todos os containers
echo "🛑 Parando containers..."
docker-compose down

# Remover imagens existentes para forçar reconstrução
echo "🗑️ Removendo imagens antigas..."
docker rmi laravel-engineer:latest 2>/dev/null || true

# Reconstruir e iniciar
echo "🔨 Reconstruindo containers..."
docker-compose build --no-cache

echo "▶️ Iniciando containers..."
docker-compose up -d

echo "⏳ Aguardando inicialização..."
sleep 5

# Verificar status
echo "📊 Status dos containers:"
docker-compose ps

echo "✅ Reinicialização concluída!"
echo "🌐 Acesse o Laravel em: http://localhost"
