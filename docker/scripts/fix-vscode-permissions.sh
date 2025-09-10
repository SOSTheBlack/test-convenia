#!/bin/bash

# Script para corrigir permissões especificamente para desenvolvimento com VSCode
# Deve ser executado no host, não dentro do container

echo "🔧 Corrigindo permissões para desenvolvimento com VSCode..."

# Obter o ID do usuário e grupo atual
USER_ID=$(id -u)
GROUP_ID=$(id -g)
USERNAME=$(whoami)

# Informações
echo "👤 Ajustando permissões para: $USERNAME (UID: $USER_ID, GID: $GROUP_ID)"

# Definir o proprietário para o usuário atual (necessário para VSCode)
echo "📋 Ajustando proprietário de todos os arquivos..."
sudo chown -R $USER_ID:$GROUP_ID .

# Ajustar permissões específicas para diretórios
echo "🗂️ Ajustando permissões de diretórios para 775..."
find . -type d -exec sudo chmod 775 {} \;

# Ajustar permissões específicas para arquivos
echo "📄 Ajustando permissões de arquivos para 664..."
find . -type f -exec sudo chmod 664 {} \;

# Garantir permissões de execução para scripts importantes
echo "🔑 Garantindo permissões de execução para scripts..."
sudo chmod +x artisan
if [ -f docker-run ]; then
  sudo chmod +x docker-run
fi
find docker/scripts -type f -name "*.sh" -exec sudo chmod +x {} \;

# Garantir permissões especiais para diretórios de cache/storage
echo "⚙️ Ajustando permissões especiais para diretórios de cache/storage..."
sudo chmod -R 777 storage
sudo chmod -R 777 bootstrap/cache
sudo chmod -R 775 database

echo "✅ Permissões corrigidas com sucesso!"
echo "🌟 Você agora deve poder editar todos os arquivos no VSCode!"
