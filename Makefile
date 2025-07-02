# ====================================
# Laravel MCP - Makefile
# ====================================

.PHONY: help build up down logs clean restart shell test

# Configuração padrão
COMPOSE_FILE=docker-compose.yml
ENV_FILE=.env

help: ## Mostrar ajuda
	@echo "Laravel MCP - Comandos Disponíveis:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Construir containers
	@echo "🔨 Construindo containers..."
	docker-compose --env-file $(ENV_FILE) build

up: ## Subir aplicação (completa)
	@echo "🚀 Subindo aplicação..."
	docker-compose --env-file $(ENV_FILE) up -d --build
	@echo ""
	@echo "✅ Aplicação disponível em:"
	@echo "🌐 Laravel: http://localhost:8000"
	@echo "🤖 Ollama: http://localhost:11434"

down: ## Parar aplicação
	@echo "🛑 Parando aplicação..."
	docker-compose --env-file $(ENV_FILE) down

logs: ## Ver logs
	docker-compose --env-file $(ENV_FILE) logs -f

logs-laravel: ## Ver logs do Laravel
	docker-compose --env-file $(ENV_FILE) logs -f laravel

logs-ollama: ## Ver logs do Ollama
	docker-compose --env-file $(ENV_FILE) logs -f ollama

clean: ## Limpar containers e volumes
	@echo "🧹 Limpando containers e volumes..."
	docker-compose --env-file $(ENV_FILE) down -v
	docker system prune -f

restart: ## Reiniciar aplicação
	@echo "🔄 Reiniciando aplicação..."
	make down
	make up

shell-laravel: ## Acessar shell do Laravel
	docker-compose --env-file $(ENV_FILE) exec laravel bash

shell-ollama: ## Acessar shell do Ollama
	docker-compose --env-file $(ENV_FILE) exec ollama bash

test: ## Executar testes
	docker-compose --env-file $(ENV_FILE) exec laravel php artisan test

status: ## Ver status dos serviços
	@echo "📊 Status dos serviços:"
	@echo ""
	docker-compose --env-file $(ENV_FILE) ps
	@echo ""
	@echo "🔍 Testando conectividade:"
	@curl -s http://localhost:8000 > /dev/null && echo "✅ Laravel: OK" || echo "❌ Laravel: Falha"
	@curl -s http://localhost:11434/api/tags > /dev/null && echo "✅ Ollama: OK" || echo "❌ Ollama: Falha"

# Comandos de desenvolvimento
dev-install: ## Instalar dependências (desenvolvimento)
	docker-compose --env-file $(ENV_FILE) exec laravel composer install
	docker-compose --env-file $(ENV_FILE) exec laravel npm install

dev-migrate: ## Executar migrations
	docker-compose --env-file $(ENV_FILE) exec laravel php artisan migrate

dev-seed: ## Executar seeders
	docker-compose --env-file $(ENV_FILE) exec laravel php artisan db:seed

dev-fresh: ## Reset completo do banco
	docker-compose --env-file $(ENV_FILE) exec laravel php artisan migrate:fresh --seed
