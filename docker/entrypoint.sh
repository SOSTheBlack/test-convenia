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

# Se for root, definir o proprietário corretamente com base em variáveis de ambiente
if [ "$(id -u)" = "0" ]; then
  # Verificar se UID e GID foram passados como variáveis de ambiente
  if [ ! -z "${UID}" ] && [ ! -z "${GID}" ]; then
    # Usar UID e GID do host para compatibilidade com o VSCode
    chown -R ${UID}:${GID} /var/www/html || true
  else
    # Fallback para www-data se não houver UID/GID definidos
    chown -R www-data:www-data /var/www/html/storage || true
    chown -R www-data:www-data /var/www/html/bootstrap/cache || true
    chown -R www-data:www-data /var/www/html/database || true
  fi
fi

# Limpar cache por precaução
if [ -f "/var/www/html/artisan" ]; then
  php /var/www/html/artisan cache:clear || true
  php /var/www/html/artisan view:clear || true
  php /var/www/html/artisan config:clear || true
fi

# Continuar com o comando passado (se houver)
exec "$@"
