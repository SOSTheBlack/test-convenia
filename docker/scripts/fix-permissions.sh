#!/bin/bash

# Script unificado para corrigir permissões no projeto Laravel
# Este script combina as funcionalidades de fix-permissions.sh e fix-project-permissions.sh

echo "🔧 Corrigindo permissões do projeto Laravel..."

# Processar argumentos
FULL_FIX=0
OWNER_TYPE="user"  # Pode ser "user" ou "www-data"

# Analisar opções de linha de comando
while [[ $# -gt 0 ]]; do
  key="$1"
  case $key in
    --full)
      FULL_FIX=1
      shift
      ;;
    --server)
      OWNER_TYPE="www-data"
      shift
      ;;
    --help)
      echo "Uso: $0 [--full] [--server]"
      echo ""
      echo "Opções:"
      echo "  --full    Corrige permissões de todo o projeto (não apenas diretórios de escrita)"
      echo "  --server  Define www-data como proprietário (ideal para ambiente de produção)"
      echo "  --help    Mostra esta ajuda"
      exit 0
      ;;
    *)
      echo "Opção desconhecida: $1"
      echo "Use --help para mais informações"
      exit 1
      ;;
  esac
done

# Obter o ID do usuário e grupo atual
USER_ID=$(id -u)
GROUP_ID=$(id -g)

# Verificar se o Docker está rodando
if ! docker-compose ps | grep -q "Up"; then
  echo "⚠️ Os containers Docker não estão rodando. Iniciando-os..."
  docker-compose up -d
  sleep 5
fi

# Diretórios que sempre precisam de permissão de escrita
WRITE_DIRS=(
  "/var/www/html/storage"
  "/var/www/html/storage/app"
  "/var/www/html/storage/app/public"
  "/var/www/html/storage/framework"
  "/var/www/html/storage/framework/cache"
  "/var/www/html/storage/framework/sessions"
  "/var/www/html/storage/framework/views"
  "/var/www/html/storage/logs"
  "/var/www/html/bootstrap/cache"
  "/var/www/html/database"
)

# Garantir que os diretórios existam
echo "📁 Garantindo que diretórios importantes existam..."
for DIR in "${WRITE_DIRS[@]}"; do
  docker-compose exec app mkdir -p "$DIR"
done

# Definir proprietário com base no modo
if [ "$OWNER_TYPE" = "www-data" ]; then
  echo "👤 Definindo www-data como proprietário dos diretórios de escrita..."
  docker-compose exec app chown -R www-data:www-data /var/www/html/storage
  docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
  docker-compose exec app chown -R www-data:www-data /var/www/html/database

  if [ $FULL_FIX -eq 1 ]; then
    echo "📋 Definindo www-data como proprietário de todo o projeto..."
    docker-compose exec app chown -R www-data:www-data /var/www/html
  fi
else
  echo "👤 Definindo usuário atual ($USER_ID:$GROUP_ID) como proprietário..."

  if [ $FULL_FIX -eq 1 ]; then
    echo "📋 Ajustando permissões de todo o projeto..."
    docker-compose exec app chown -R $USER_ID:$GROUP_ID /var/www/html

    # Ajustar permissões específicas para diretórios
    docker-compose exec app find /var/www/html -type d -exec chmod 755 {} \;

    # Ajustar permissões específicas para arquivos
    docker-compose exec app find /var/www/html -type f -exec chmod 644 {} \;
  else
    # Apenas ajustar os diretórios específicos
    docker-compose exec app chown -R $USER_ID:$GROUP_ID /var/www/html/storage
    docker-compose exec app chown -R $USER_ID:$GROUP_ID /var/www/html/bootstrap/cache
    docker-compose exec app chown -R $USER_ID:$GROUP_ID /var/www/html/database
  fi
fi

# Garantir que os diretórios de escrita tenham permissões adequadas
echo "🔒 Ajustando permissões dos diretórios de escrita..."
docker-compose exec app find /var/www/html/storage -type d -exec chmod 775 {} \;
docker-compose exec app find /var/www/html/storage -type f -exec chmod 664 {} \;
docker-compose exec app find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;
docker-compose exec app find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;
docker-compose exec app find /var/www/html/database -type d -exec chmod 775 {} \;
docker-compose exec app find /var/www/html/database -type f -exec chmod 664 {} \;

# Garantir permissões de execução para scripts importantes
echo "🔑 Garantindo permissões de execução para scripts importantes..."
docker-compose exec app chmod +x /var/www/html/artisan
if [ -f /var/www/html/docker-run ]; then
  docker-compose exec app chmod +x /var/www/html/docker-run
fi

# Limpar cache do Laravel
echo "🧹 Limpando cache do Laravel..."
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear

echo "✅ Permissões corrigidas com sucesso!"
echo "🌟 O projeto deve funcionar corretamente agora!"
