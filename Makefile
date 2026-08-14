.DEFAULT_GOAL := help
CONTAINER := verifactu-bundle-php

# Help
help: ## show this help message
	@grep -E '^[a-zA-Z0-9 /_-]+:.*## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}' | sort

install: build startd composer/install ## build the PHP image, start the container and install Composer dependencies
it: php-cs-fixer phpstan test ## run the full local gate: code style, static analysis and tests

# Docker
build: ## build the PHP Docker image
	docker compose build

destroy: CMD=down --remove-orphans --volumes ## stop and remove the container, network and volumes
start: CMD=up ## start the container in foreground
startd: CMD=up --detach ## start the container in background
stop: CMD=stop ## stop the container

destroy start startd stop:
	docker compose $(CMD)

bash:
	@docker exec -it $(CONTAINER) bash

shell: ## open an interactive shell into the PHP container
	docker exec -it $(CONTAINER) bash

# Composer
composer/install: ## install Composer dependencies
	docker exec $(CONTAINER) composer install

composer/outdated: ## show outdated direct Composer dependencies
	docker exec $(CONTAINER) composer outdated --direct

composer/update: ## update Composer dependencies
	docker exec $(CONTAINER) composer update

# Quality Assurance
php-cs-fixer: ## fix code style with PHP-CS-Fixer
	docker exec $(CONTAINER) vendor/bin/php-cs-fixer fix

php-cs-fixer/check: ## check code style with PHP-CS-Fixer (dry-run)
	docker exec $(CONTAINER) vendor/bin/php-cs-fixer fix --dry-run --diff

phpstan: ## run PHPStan static analysis
	docker exec $(CONTAINER) vendor/bin/phpstan analyse

test: ## run PHPUnit test suite
	docker exec $(CONTAINER) vendor/bin/phpunit --testdox
