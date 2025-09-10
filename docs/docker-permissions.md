# Ajustes nas Permissões do Docker para Laravel (VSCode)

## Problema Original

O ambiente Docker estava configurado de forma que todos os arquivos e diretórios no projeto Laravel estavam atribuídos ao usuário e grupo `www-data:www-data`, com permissões que não permitiam ao usuário local (`garcia`) editar os arquivos pelo VSCode.

## Alterações Realizadas

### 1. Permissões Imediatas
- Corrigimos as permissões de todos os arquivos do projeto para o usuário local (`garcia:garcia`) usando `chown`
- Isso permite a edição imediata dos arquivos pelo VSCode

### 2. Docker Compose
- Descomentamos e reativamos a configuração `user: "${UID:-1000}:${GID:-1000}"` para garantir que o container execute com o mesmo UID/GID do usuário host
- Isso garante que novos arquivos criados dentro do container tenham o proprietário correto no host

### 3. Dockerfile
- Modificamos a configuração de permissões para não forçar o proprietário `www-data`, apenas ajustando as permissões dos arquivos
- Isso permite maior flexibilidade com relação ao proprietário dos arquivos

### 4. Script de Entrypoint
- Atualizamos o script para verificar a existência de variáveis de ambiente `UID` e `GID` e usá-las para definir o proprietário dos arquivos
- Mantivemos a atribuição de permissões especiais para diretórios críticos (storage, cache, etc.)

### 5. Novo Script Auxiliar
- Criamos o script `fix-vscode-permissions.sh` para facilitar o ajuste de permissões no ambiente de desenvolvimento com VSCode
- Este script pode ser executado a qualquer momento para corrigir problemas de permissão

### 6. Makefile
- Adicionamos o comando `make fix-vscode` para facilitar o acesso ao novo script

## Como Usar

Para corrigir permissões a qualquer momento, execute:

```bash
make fix-vscode
```

## Comportamento Esperado

1. Todos os arquivos do projeto agora devem ser editáveis pelo VSCode
2. Novos arquivos criados pelo container terão o proprietário correto (seu usuário local)
3. Os diretórios críticos do Laravel (storage, cache, etc.) continuarão com permissões adequadas para o funcionamento da aplicação
4. O ambiente Docker continuará funcionando normalmente

## Observações de Segurança

- Esta configuração é adequada apenas para ambientes de desenvolvimento local
- Para ambientes de produção, seria recomendado usar o usuário `www-data` por questões de segurança
