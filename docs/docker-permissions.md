# Solução Definitiva de Permissões do Docker para Laravel (VSCode)

## Problemas Originais

1. **Permissões de Arquivos**: Todos os arquivos e diretórios no projeto Laravel estavam atribuídos ao usuário e grupo `www-data:www-data`, com permissões que não permitiam ao usuário local editar os arquivos pelo VSCode.

2. **Erro de Permissões no Laravel**: Ao corrigir as permissões para o VSCode, o Laravel passa a apresentar erros como "Failed to open stream: Permission denied" ao tentar escrever em pastas como `storage/framework/views`.

3. **Erro de Permissões em Scripts**: Muitos scripts do projeto (`docker-run setup`, `docker-run restart`, etc.) apresentavam erros de permissão.

4. **Inconsistência de Permissões**: A necessidade constante de rodar scripts de correção (`fix-vscode-permissions.sh`, `fix-container-permissions.sh`) causava confusão e perda de tempo.

## Nova Solução Implementada

Nossa nova abordagem resolve o problema de forma definitiva, sem necessidade de scripts de correção após a primeira execução:

### 1. Criação de Usuário no Container com Mesmo UID/GID do Host

- Criamos um usuário `devuser` no container com o mesmo UID/GID do seu usuário no host (detectado automaticamente)
- Este usuário pertence ao grupo `www-data` e tem permissões sudo
- Todos os arquivos dentro do container pertencem a este usuário

### 2. Configuração Correta de Permissões

- Diretórios: `775` (rwxrwxr-x) - permitindo escrita pelo usuário, grupo e apenas leitura para outros
- Arquivos: `664` (rw-rw-r--) - permitindo leitura/escrita pelo usuário e grupo, apenas leitura para outros
- Diretórios críticos (`storage` e `bootstrap/cache`): configurados para serem proprietários de `www-data:devgroup`

### 3. Docker Compose Atualizado

- Todos os serviços usam o usuário `devuser` por padrão
- UID/GID são passados como argumentos de build
- Volumes configurados com opção `cached` para melhor performance

### 4. Script de Inicialização Automático

- Adicionamos o comando `./docker-run start` que configura tudo automaticamente:
  - Detecta o UID/GID do usuário atual
  - Atualiza `.env.docker` com esses valores
  - Constrói e inicia os containers com as permissões corretas
  - Configura todos os diretórios e permissões

## Como Usar (NOVA FORMA RECOMENDADA)

Para iniciar o ambiente com todas as permissões configuradas corretamente, use:

```bash
./docker-run start
```

Este comando único:
1. Detecta o UID/GID do seu usuário automaticamente
2. Constrói os containers com esse UID/GID
3. Inicia o ambiente Docker
4. Configura todas as permissões necessárias
5. Limpa os caches do Laravel

## Por Que Esta Solução Funciona

1. **Correspondência de Usuários**: O usuário `devuser` no container tem exatamente o mesmo UID/GID do seu usuário no host, eliminando problemas de propriedade de arquivos.

2. **Grupos Compartilhados**: Tanto `devuser` quanto `www-data` podem acessar e modificar os arquivos necessários através de permissões de grupo.

3. **Permissões Adequadas**: As permissões são configuradas para permitir acesso de leitura/escrita aos usuários corretos, sem abrir demais a segurança.

4. **Automatização Completa**: Todo o processo é automatizado, sem necessidade de intervenção manual após a configuração inicial.

## Observações Importantes

- Esta solução funciona para equipes de desenvolvimento, pois detecta automaticamente o UID/GID de cada desenvolvedor
- O VSCode pode editar todos os arquivos normalmente
- O Laravel pode escrever logs e arquivos de cache normalmente
- Os comandos Git funcionam sem problemas
- Scripts podem ser executados sem erros de permissão
- Não há necessidade de rodar scripts adicionais de correção após a inicialização
