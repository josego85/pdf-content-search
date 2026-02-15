# ============================================
# PDF Content Search - Makefile
# ============================================
# Single entry point for all operations
# ============================================

.DEFAULT_GOAL := help
.PHONY: help dev prod up down restart logs shell test phpstan clean rebuild status

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
RED := \033[0;31m
NC := \033[0m # No Color

# Docker Compose commands
COMPOSE_DEV := docker compose -f docker-compose.yml -f docker-compose.dev.yml -p pdf-content-search
COMPOSE_PROD := docker compose -f docker-compose.yml -f docker-compose.prod.yml -p pdf-content-search-prod

# Environment detection (default to dev)
ENV ?= dev
ifeq ($(ENV),prod)
	COMPOSE = $(COMPOSE_PROD)
	ENV_NAME = PRODUCTION
	PORT = 8080
else
	COMPOSE = $(COMPOSE_DEV)
	ENV_NAME = DEVELOPMENT
	PORT = 80
endif

# ============================================
# Help
# ============================================

help: ## Show this help message
	@echo ""
	@echo "$(BLUE)PDF Content Search - Available Commands$(NC)"
	@echo ""
	@echo "$(GREEN)Quick Start:$(NC)"
	@echo "  $(YELLOW)make dev$(NC)              Start development environment (with build)"
	@echo "  $(YELLOW)make prod$(NC)             Start production environment (with build)"
	@echo "  $(YELLOW)make up$(NC)               Start without rebuild (faster, ENV=dev|prod)"
	@echo "  $(YELLOW)make down$(NC)             Stop current environment"
	@echo "  $(YELLOW)make logs$(NC)             View logs"
	@echo "  $(YELLOW)make shell$(NC)            Open shell in container"
	@echo ""
	@echo "$(GREEN)Environment-specific:$(NC)"
	@echo "  $(YELLOW)make dev$(NC)              Start development at http://localhost"
	@echo "  $(YELLOW)make prod$(NC)             Start production at http://localhost:8080"
	@echo "  $(YELLOW)make up$(NC)               Start development without rebuild"
	@echo "  $(YELLOW)make up ENV=prod$(NC)      Start production without rebuild"
	@echo "  $(YELLOW)make down ENV=dev$(NC)     Stop development"
	@echo "  $(YELLOW)make down ENV=prod$(NC)    Stop production"
	@echo "  $(YELLOW)make logs ENV=prod$(NC)    View production logs"
	@echo ""
	@echo "$(GREEN)Utilities:$(NC)"
	@echo "  $(YELLOW)make status$(NC)           Show all environments status"
	@echo "  $(YELLOW)make restart$(NC)          Restart environment (ENV=dev|prod)"
	@echo "  $(YELLOW)make rebuild$(NC)          Rebuild images (ENV=dev|prod)"
	@echo "  $(YELLOW)make clean$(NC)            Remove volumes - DESTRUCTIVE (ENV=dev|prod)"
	@echo "  $(YELLOW)make test$(NC)             Run tests"
	@echo "  $(YELLOW)make phpstan$(NC)          Run PHPStan static analysis"
	@echo ""
	@echo "$(GREEN)Examples:$(NC)"
	@echo "  $(YELLOW)make dev$(NC)              # Start development"
	@echo "  $(YELLOW)make logs SERVICE=php$(NC) # View PHP logs"
	@echo "  $(YELLOW)make shell ENV=prod$(NC)   # Shell in production container"
	@echo "  $(YELLOW)make clean ENV=dev$(NC)    # Clean development data"
	@echo ""

# ============================================
# Main Commands
# ============================================

dev: ## Start development environment
	@echo "$(BLUE)🚀 Starting DEVELOPMENT environment...$(NC)"
	@if [ ! -f .env ]; then \
		echo "$(RED)❌ ERROR: .env not found!$(NC)"; \
		echo "The .env file should be in Git. If missing, restore it from repository."; \
		exit 1; \
	fi
	@$(COMPOSE_DEV) up -d --build
	@$(MAKE) --no-print-directory _wait-for-services ENV=dev
	@$(MAKE) --no-print-directory _init ENV=dev
	@echo "$(GREEN)✅ Development ready at http://localhost$(NC)"

up: ## Start environment without rebuilding (ENV=dev|prod, default: dev)
	@echo "$(BLUE)▶️  Starting $(shell echo $(ENV) | tr '[:lower:]' '[:upper:]') environment (no rebuild)...$(NC)"
	@if [ "$(ENV)" = "prod" ]; then \
		$(COMPOSE_PROD) up -d; \
	else \
		$(COMPOSE_DEV) up -d; \
	fi
	@echo "$(GREEN)✅ Services started$(NC)"

prod: _check-prod-env ## Start production environment
	@echo "$(BLUE)🚀 Starting PRODUCTION environment...$(NC)"
	@$(COMPOSE_PROD) up -d --build
	@$(MAKE) --no-print-directory _wait-for-services ENV=prod
	@$(MAKE) --no-print-directory _init ENV=prod
	@echo "$(GREEN)✅ Production ready at http://localhost:8080$(NC)"

down: ## Stop environment (ENV=dev|prod, default: dev)
	@echo "$(YELLOW)🛑 Stopping $(ENV_NAME) environment...$(NC)"
	@$(COMPOSE) down
	@echo "$(GREEN)✅ Stopped$(NC)"

restart: ## Restart environment (ENV=dev|prod, default: dev)
	@$(MAKE) --no-print-directory down ENV=$(ENV)
	@$(MAKE) --no-print-directory $(ENV)

logs: ## Show logs (ENV=dev|prod, SERVICE=service_name)
	@$(COMPOSE) logs -f $(SERVICE)

shell: ## Open shell in container (ENV=dev|prod, default: dev)
ifeq ($(ENV),prod)
	@$(COMPOSE) exec php sh
else
	@$(COMPOSE) exec php bash
endif

status: ## Show status of all environments
	@echo "$(BLUE)Development Environment:$(NC)"
	@$(COMPOSE_DEV) ps 2>/dev/null || echo "  Not running"
	@echo ""
	@echo "$(BLUE)Production Environment:$(NC)"
	@$(COMPOSE_PROD) ps 2>/dev/null || echo "  Not running"

# ============================================
# Build & Cleanup
# ============================================

rebuild: ## Rebuild images (ENV=dev|prod, default: dev)
	@echo "$(BLUE)🔨 Rebuilding $(ENV_NAME) images...$(NC)"
	@$(COMPOSE) build --no-cache
	@echo "$(GREEN)✅ Rebuild complete$(NC)"

clean: ## Remove ALL data for environment (ENV=dev|prod) - DESTRUCTIVE
	@echo "$(RED)⚠️  WARNING: This will delete ALL $(ENV_NAME) data!$(NC)"
	@echo "Volumes, databases, elasticsearch indices, uploaded PDFs - everything will be lost."
	@read -p "Type '$(ENV)' to confirm: " confirm; \
	if [ "$$confirm" = "$(ENV)" ]; then \
		$(COMPOSE) down -v; \
		echo "$(GREEN)✅ $(ENV_NAME) volumes removed$(NC)"; \
	else \
		echo "$(YELLOW)❌ Cancelled (you typed: $$confirm, expected: $(ENV))$(NC)"; \
	fi

test: ## Run tests in development environment
	@$(COMPOSE_DEV) exec php php bin/phpunit

phpstan: ## Run PHPStan static analysis
	@$(COMPOSE_DEV) exec php composer phpstan

# ============================================
# Internal Helpers (don't call directly)
# ============================================

_wait-for-services: ## Internal: Wait for services to be ready
	@echo "$(BLUE)⏳ Waiting for services to be healthy...$(NC)"
	@echo "  → Waiting for database..."
	@for i in $$(seq 1 30); do \
		if [ "$$(docker inspect --format='{{.State.Health.Status}}' $$($(COMPOSE) ps -q database) 2>/dev/null)" = "healthy" ]; then \
			echo "  $(GREEN)✓ Database ready$(NC)"; \
			break; \
		fi; \
		if [ $$i -eq 30 ]; then \
			echo "  $(RED)✗ Database timeout after 60s$(NC)"; \
			exit 1; \
		fi; \
		sleep 2; \
	done
	@echo "  → Waiting for PHP-FPM..."
	@sleep 3
	@echo "  $(GREEN)✓ All services ready$(NC)"

_init: ## Internal: Initialize environment
	@echo "$(BLUE)🔧 Running database migrations...$(NC)"
	@$(COMPOSE) exec -T php php bin/console doctrine:migrations:migrate --no-interaction 2>/dev/null || true
	@echo "$(BLUE)📨 Setting up Messenger transports...$(NC)"
	@if ! $(COMPOSE) exec -T php php bin/console messenger:setup-transports 2>&1 | grep -q "successfully"; then \
		echo "$(RED)✗ Failed to setup Messenger transports$(NC)"; \
		$(COMPOSE) exec -T php php bin/console messenger:setup-transports; \
	fi
	@echo "$(BLUE)📊 Creating Elasticsearch index...$(NC)"
	@$(COMPOSE) exec -T php php bin/console app:create-pdf-index 2>/dev/null || echo "$(YELLOW)Index already exists$(NC)"
	@echo "$(BLUE)🤖 Checking Ollama models...$(NC)"
	@$(COMPOSE) exec -T ollama /usr/local/bin/download-models.sh 2>/dev/null || true
ifeq ($(ENV),dev)
	@echo "$(BLUE)📦 Building frontend assets...$(NC)"
	@$(COMPOSE) exec -T php npm run build 2>/dev/null || true
endif

_check-prod-env: ## Internal: Validate production environment
	@if [ ! -f .env.prod ]; then \
		echo "$(RED)❌ ERROR: .env.prod not found!$(NC)"; \
		echo ""; \
		echo "$(YELLOW)The .env.prod file should exist. If missing:$(NC)"; \
		echo "  git restore .env.prod"; \
		echo ""; \
		exit 1; \
	fi
	@if [ ! -f .env.prod.local ]; then \
		echo "$(RED)❌ ERROR: .env.prod.local not found!$(NC)"; \
		echo ""; \
		echo "$(YELLOW)Create production secrets:$(NC)"; \
		echo "  ./bin/init-prod-secrets.sh"; \
		echo ""; \
		echo "This will generate:"; \
		echo "  - APP_SECRET"; \
		echo "  - POSTGRES_PASSWORD"; \
		echo "  - ELASTIC_PASSWORD"; \
		echo ""; \
		exit 1; \
	fi
	@echo "$(GREEN)✅ Production configuration valid$(NC)"
