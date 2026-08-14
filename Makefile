.PHONY: help up down restart build rebuild logs shell psql redis cache-clear migrate seed fresh test lint format install health

DOCKER_COMPOSE = docker compose
APP_SERVICE = app
NGINX_PORT ?= 8080

help: ## Lista comandos disponíveis
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Sobe o ambiente Docker
	$(DOCKER_COMPOSE) up -d

down: ## Para o ambiente Docker
	$(DOCKER_COMPOSE) down

restart: ## Reinicia os containers
	$(DOCKER_COMPOSE) restart

build: ## Constrói as imagens Docker
	$(DOCKER_COMPOSE) build

rebuild: ## Reconstrói containers do zero
	$(DOCKER_COMPOSE) down
	$(DOCKER_COMPOSE) build --no-cache
	$(DOCKER_COMPOSE) up -d

logs: ## Exibe logs dos containers
	$(DOCKER_COMPOSE) logs -f

shell: ## Acessa o container PHP (app)
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) bash

psql: ## Acessa o PostgreSQL
	$(DOCKER_COMPOSE) exec postgres psql -U gyntraining -d gyntraining

redis: ## Acessa o Redis CLI
	$(DOCKER_COMPOSE) exec redis redis-cli

install: ## Instala dependências PHP e Node
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) composer install
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) npm install

migrate: ## Executa migrations
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan migrate

seed: ## Executa seeders
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan db:seed

fresh: ## Recria banco com migrations e seeders
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan migrate:fresh --seed

test: ## Executa testes automatizados
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan test

lint: ## Executa Laravel Pint (dry-run)
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) ./vendor/bin/pint --test

format: ## Formata código com Laravel Pint
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) ./vendor/bin/pint

cache-clear: ## Limpa caches da aplicação
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan optimize:clear

health: ## Verifica health check da aplicação
	@curl -sf http://localhost:$(NGINX_PORT)/up && echo "\nHealth check OK" || echo "\nHealth check FAILED"

docs-export: ## Exporta spec OpenAPI para docs/openapi.json
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan scramble:export --path=docs/openapi.json

frontend-build: ## Compila assets Vue/Vite (requer Docker)
	docker run --rm -v "$(PWD)":/app -w /app node:22 sh -c "npm install && npm run build"

frontend-dev: ## Sobe Vite dev server local
	npm run dev

docs: docs-export ## Alias para exportar documentacao OpenAPI

setup: build up install migrate ## Setup inicial completo do projeto
