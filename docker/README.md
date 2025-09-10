# Arquivos Docker organizados

A estrutura de diretórios para os arquivos Docker foi reorganizada:

## Estrutura

```
docker/
├── nginx/              # Configurações do Nginx
├── php/                # Configurações do PHP
├── scripts/            # Scripts de utilitários 
├── supervisor/         # Configurações do Supervisor
└── entrypoint.sh       # Script de inicialização
```

## Uso

Use o script ponte `./docker-run` para acessar os scripts:

```bash
# Configuração inicial
./docker-run setup

# Executar testes
./docker-run test

# Reiniciar containers
./docker-run restart 

# Corrigir problemas de permissão
./docker-run fix-permissions

# Corrigir problemas do Git
./docker-run fix-git
```

Ou use os comandos do Makefile:

```bash
make setup
make test
make rebuild
make fix-permissions
make fix-git
```
