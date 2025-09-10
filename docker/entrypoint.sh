#!/bin/bash

# Função para ajustar permissões de qualquer arquivo criado pelo PHP
setup_php_permissions() {
    # Configurar umask para garantir permissões corretas para novos arquivos
    echo "umask 0000" >> /etc/bash.bashrc
    echo "Permissões PHP configuradas!"
}

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

    # Definir proprietário correto para todo o projeto
    # Usar o UID e GID do host (normalmente 1000:1000)
    chown -R ${USER_ID:-1000}:${GROUP_ID:-1000} /var/www/html

    # Garantir que o PHP-FPM (www-data) ainda tenha acesso para escrita em pastas específicas
    chown -R www-data:www-data /var/www/html/database
    chown -R www-data:www-data /var/www/html/storage
    chown -R www-data:www-data /var/www/html/bootstrap/cache

    # Garantir permissões para logs do supervisor
    touch /var/log/supervisor/supervisord.log
    chmod 777 /var/log/supervisor/supervisord.log
    chmod 777 /var/log/supervisor

    # Garantir que tanto www-data quanto devuser podem escrever
    chmod -R 777 /var/www/html/storage
    chmod -R 777 /var/www/html/bootstrap/cache

    # Garantir que o diretório app tenha permissões adequadas para o VS Code editar
    chmod -R 777 /var/www/html/app

    # Garantir que o diretório routes tenha permissões adequadas
    chmod -R 777 /var/www/html/routes

    # Garantir que o diretório tests tenha permissões adequadas
    chmod -R 777 /var/www/html/tests

    # Garantir que o diretório resources tenha permissões adequadas
    chmod -R 777 /var/www/html/resources

    # Garantir que scripts são executáveis
    chmod +x /var/www/html/artisan
    find /var/www/html/docker/scripts -type f -name "*.sh" -exec chmod +x {} \;

    # Corrigir problemas de permissão em arquivos específicos
    find /var/www/html -type f -not -path "*/vendor/*" -not -path "*/node_modules/*" -exec chmod 666 {} \;
}

# Configurar diretório Git como seguro
git config --global --add safe.directory /var/www/html

# Verificar se o servidor web está sendo executado como root
if [ "$(id -u)" = "0" ]; then
    echo "Executando como root. Configurando permissões..."
    setup_directories
    setup_php_permissions

    # Criar arquivo de pid para o supervisor se não existir
    touch /var/run/supervisord.pid
    chmod 777 /var/run/supervisord.pid

    # Garantir que qualquer arquivo criado pelo PHP (Artisan) tenha as permissões corretas
    echo "umask 0000" >> /etc/bash.bashrc

    # Configurar PHP-FPM para usar o UID/GID correto
    if [ -d "/usr/local/etc/php-fpm.d/" ]; then
        echo "
[www]
user = www-data
group = www-data
listen = 9000
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
php_admin_flag[log_errors] = on
php_admin_value[error_log] = /var/log/php-fpm.log
" > /usr/local/etc/php-fpm.d/zzz-custom.conf
    fi

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
