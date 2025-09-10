#!/bin/bash

# Função para criar diretórios necessários com permissões corretas
setup_directories() {
    # Criar diretórios necessários
    mkdir -p /var/www/html/storage/framework/sessions
    mkdir -p /var/www/html/storage/framework/views
    mkdir -p /var/www/html/storage/framework/cache
    mkdir -p /var/www/html/bootstrap/cache
    mkdir -p /var/log/supervisor
    mkdir -p /var/www/html/database

    # Garantir permissões para o banco de dados SQLite
    touch /var/www/html/database/database.sqlite
    chown -R www-data:www-data /var/www/html/database
    chmod -R 777 /var/www/html/database
    chmod 777 /var/www/html/database/database.sqlite

    # Definir permissões para diretórios que precisam de escrita
    chown -R www-data:www-data /var/www/html/storage
    chown -R www-data:www-data /var/www/html/bootstrap/cache

    # Garantir permissões para logs do supervisor
    touch /var/log/supervisor/supervisord.log
    chmod 777 /var/log/supervisor/supervisord.log
    chmod 777 /var/log/supervisor

    # Garantir que tanto www-data quanto devuser podem escrever
    chmod -R 777 /var/www/html/storage
    chmod -R 777 /var/www/html/bootstrap/cache

    # Garantir que scripts são executáveis
    chmod +x /var/www/html/artisan
    find /var/www/html/docker/scripts -type f -name "*.sh" -exec chmod +x {} \;
}

# Configurar diretório Git como seguro
git config --global --add safe.directory /var/www/html

# Verificar se o servidor web está sendo executado como root
if [ "$(id -u)" = "0" ]; then
    echo "Executando como root. Configurando permissões..."
    setup_directories

    # Criar arquivo de pid para o supervisor se não existir
    touch /var/run/supervisord.pid
    chmod 777 /var/run/supervisord.pid

    # Iniciar o servidor web como root (necessário para porta 80)
    exec "$@"
else
    echo "Executando como usuário não-root ($(id -u):$(id -g))"

    # Se estivermos rodando como devuser, executamos comando diretamente
    # Esta parte vai acontecer para comandos como artisan, composer etc.
    if [ -f "/var/www/html/artisan" ]; then
        # Limpar caches existentes
        php /var/www/html/artisan cache:clear 2>/dev/null || true
        php /var/www/html/artisan view:clear 2>/dev/null || true
        php /var/www/html/artisan config:clear 2>/dev/null || true
    fi

    # Executar o comando passado
    exec "$@"
fi
