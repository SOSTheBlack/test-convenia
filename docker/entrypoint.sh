#!/bin/bash

# Configurar Git para aceitar o diretório como seguro - apenas se for root
if [ "$(id -u)" = "0" ]; then
  git config --global --add safe.directory /var/www/html
else
  # Se não for root, configurar apenas para o usuário atual
  git config --add safe.directory /var/www/html 2>/dev/null || true
fi

# Ajustar permissões de diretórios importantes do Laravel
echo "Configurando permissões..."

# Verificar e criar diretórios necessários se não existirem
mkdir -p /var/www/html/storage/framework/sessions 2>/dev/null || true
mkdir -p /var/www/html/storage/framework/views 2>/dev/null || true
mkdir -p /var/www/html/storage/framework/cache 2>/dev/null || true
mkdir -p /var/www/html/bootstrap/cache 2>/dev/null || true

# Configurar permissões para qualquer usuário (modificado)
chmod -R 777 /var/www/html/storage || true
chmod -R 777 /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/database || true

# Se for root, aplicar permissões para que www-data possa escrever, mas o usuário host também
if [ "$(id -u)" = "0" ]; then
  # Obter UID e GID do host a partir das variáveis de ambiente ou do arquivo
  HOST_UID=${HOST_UID:-1000}
  HOST_GID=${HOST_GID:-1000}
  
  # Configurar permissões para ambiente de desenvolvimento
  echo "Ajustando permissões como root (UID: $HOST_UID, GID: $HOST_GID)..."
  
  # Define www-data como proprietário dos diretórios essenciais
  chown -R www-data:www-data /var/www/html/storage || true
  chown -R www-data:www-data /var/www/html/bootstrap/cache || true
  chown -R www-data:www-data /var/www/html/database || true
  
  # Garante que o usuário host possa ler/escrever em todos os arquivos
  chmod -R 775 /var/www/html || true
fi

# Limpar cache por precaução
if [ -f "/var/www/html/artisan" ]; then
  php /var/www/html/artisan cache:clear || true
  php /var/www/html/artisan view:clear || true
  php /var/www/html/artisan config:clear || true
fi

# Continuar com o comando passado (se houver)
exec "$@"
