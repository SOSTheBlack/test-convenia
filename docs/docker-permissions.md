# Ajustes nas Permissões do Docker para Laravel (VSCode)

## Problemas Originais

1. **Permissões de Arquivos**: Todos os arquivos e diretórios no projeto Laravel estavam atribuídos ao usuário e grupo `www-data:www-data`, com permissões que não permitiam ao usuário local (`garcia`) editar os arquivos pelo VSCode.

2. **Erro no Supervisor**: Ao tentar configurar o container para rodar com o usuário local (para resolver o problema #1), o supervisor apresentava o erro: "Can't drop privilege as nonroot user"

## Alterações Realizadas

### 1. Permissões Imediatas
- Corrigimos as permissões de todos os arquivos do projeto para o usuário local (`garcia:garcia`) usando `chown`
- Isso permite a edição imediata dos arquivos pelo VSCode

### 2. Docker Compose
- **Atualização**: Mantivemos o container rodando como `root` (comentando a opção `user`) para evitar o erro com o supervisor
- Configuramos o entrypoint para garantir permissões adequadas mesmo com o container rodando como root

### 3. Dockerfile
- Modificamos a configuração de permissões para não forçar o proprietário `www-data`, apenas ajustando as permissões dos arquivos
- Definimos permissões mais abertas (775 para diretórios, 664 para arquivos) para permitir edição tanto pelo usuário local quanto pelo usuário web

### 4. Script de Entrypoint
- Atualizamos o script para ajustar permissões independentemente de quem está executando o container
- Garantimos que tanto o usuário local quanto o `www-data` possam acessar os arquivos necessários

### 5. Configuração do Supervisor
- Ajustamos o arquivo `supervisord.conf` para funcionar corretamente em um ambiente onde o supervisor roda como root
- Adicionamos a configuração `user=root` explícita para cada programa gerenciado pelo supervisor

### 6. Novos Scripts Auxiliares
- Criamos o script `fix-vscode-permissions.sh` para facilitar o ajuste de permissões no ambiente de desenvolvimento com VSCode
- Criamos o script `fix-container-permissions.sh` para corrigir permissões dentro do container em execução

### 7. Makefile
- Adicionamos o comando `make fix-vscode` para facilitar o acesso ao novo script

## Como Usar

Para corrigir permissões a qualquer momento, execute:

```bash
# Para corrigir permissões para desenvolvimento com VSCode (executa no host)
make fix-vscode

# OU para corrigir permissões dentro do container em execução
docker/scripts/fix-container-permissions.sh
```

## Comportamento Esperado

1. Todos os arquivos do projeto agora devem ser editáveis pelo VSCode
2. Os containers Docker devem iniciar sem erros de privilégios
3. Os diretórios críticos do Laravel (storage, cache, etc.) continuarão com permissões adequadas para o funcionamento da aplicação
4. O ambiente Docker continuará funcionando normalmente

## Observações de Segurança

- Esta configuração é adequada apenas para ambientes de desenvolvimento local
- Para ambientes de produção, seria recomendado usar configurações de segurança mais rigorosas
- O uso do usuário root no container de desenvolvimento é um compromisso para resolver o problema de permissões, mas não é recomendado para produção
