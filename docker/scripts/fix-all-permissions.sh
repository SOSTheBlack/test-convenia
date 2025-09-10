#!/bin/bash

# Determina o UID e GID do usuário atual
USER_ID=$(id -u)
GROUP_ID=$(id -g)

# Imprime informações para debug
echo "Configurando permissões para UID:GID $USER_ID:$GROUP_ID"

# Parar os containers para modificar permissões
echo "Parando containers Docker..."
docker-compose down

# Dar permissões de escrita total ao diretório do projeto
echo "Aplicando permissões ao diretório do projeto..."
sudo chown -R $USER_ID:$GROUP_ID .

# Garantir permissões específicas para diretórios críticos
echo "Configurando permissões específicas..."
sudo find . -type d -exec chmod 775 {} \;
sudo find . -type f -exec chmod 664 {} \;

# Permissões especiais para diretórios que precisam de escrita
sudo chmod -R 777 storage
sudo chmod -R 777 bootstrap/cache
sudo chmod -R 777 database
sudo chmod -R 777 app
sudo chmod -R 777 routes
sudo chmod -R 777 tests
sudo chmod -R 777 resources

# Permissões para os scripts
sudo chmod +x artisan
sudo chmod +x docker-run
sudo find ./docker/scripts -type f -name "*.sh" -exec chmod +x {} \;

# Atualizar .env com UID/GID corretos
if [ -f ".env" ]; then
    echo "Atualizando .env com UID/GID corretos..."
    # Verifica se USER_ID e GROUP_ID já existem no .env
    grep -q "USER_ID" .env
    if [ $? -eq 0 ]; then
        # Substitui valores existentes
        sed -i "s/USER_ID=.*/USER_ID=$USER_ID/" .env
        sed -i "s/GROUP_ID=.*/GROUP_ID=$GROUP_ID/" .env
    else
        # Adiciona novos valores
        echo "USER_ID=$USER_ID" >> .env
        echo "GROUP_ID=$GROUP_ID" >> .env
    fi
else
    echo "ERRO: Arquivo .env não encontrado!"
    exit 1
fi

# Reconstruir os containers para aplicar as novas configurações
echo "Reconstruindo containers com novas permissões..."
docker-compose build
docker-compose up -d

echo "Permissões configuradas e containers reiniciados!"
echo "Agora você deve conseguir criar arquivos através do VS Code."
