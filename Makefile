# Makefile para Laravel Engineer

.PHONY: help setup up down build logs shell test clean install

# Variáveis
DOCKER_COMPOSE = docker-compose
CONTAINER_APP = app
DOCKER_SCRIPTS = docker/scripts

help: ## Mostra este help
	@echo "Comandos disponíveis:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

setup: ## Setup inicial completo do projeto
	$(DOCKER_SCRIPTS)/docker-setup.sh

up: ## Inicia os containers
	$(DOCKER_COMPOSE) up -d

down: ## Para os containers
	$(DOCKER_COMPOSE) down

build: ## Reconstrói os containers
	$(DOCKER_COMPOSE) build --no-cache

logs: ## Mostra logs do container principal
	$(DOCKER_COMPOSE) logs -f $(CONTAINER_APP)

shell: ## Acessa o shell do container principal
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) bash

test: ## Executa os testes
	$(DOCKER_SCRIPTS)/docker-test.sh

install: ## Instala dependências composer
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) composer install

clean: ## Limpa cache e otimiza
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) php artisan config:clear
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) php artisan cache:clear
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) php artisan route:clear
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) php artisan view:clear

migrate: ## Executa migrations
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) php artisan migrate

migrate-fresh: ## Executa migrations do zero
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) php artisan migrate:fresh --seed

tinker: ## Acessa o Laravel Tinker
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) php artisan tinker

queue: ## Inicia worker de queue
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) php artisan queue:work

passport: ## Instala Laravel Passport
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) php artisan passport:install

seed: ## Executa seeders
	$(DOCKER_COMPOSE) exec $(CONTAINER_APP) php artisan db:seed

status: ## Mostra status dos containers
	$(DOCKER_COMPOSE) ps

restart: down up ## Reinicia os containers

rebuild: ## Reconstruir e reiniciar completamente os containers
	$(DOCKER_SCRIPTS)/restart-docker.sh

fix: ## Exibe ajuda sobre comandos de permissão
	@echo "Comandos de permissão disponíveis:"
	@echo "  make fix-permissions  - Corrige permissões dos diretórios de escrita"
	@echo "  make fix-all         - Corrige permissões de todos os arquivos"
	@echo "  make fix-server      - Configura permissões para ambiente de produção"
	@echo "  make fix-git         - Corrige problema de propriedade do Git"
	@echo "  make fix-vscode      - Corrige permissões para desenvolvimento com VSCode"
	@echo ""
	@echo "Para opções avançadas: ./docker-run fix-permissions --help"
