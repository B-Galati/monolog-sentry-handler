COMPOSER_INSTALL_FLAGS ?=
COMPOSER_UPDATE_FLAGS ?=

.DEFAULT_GOAL := help
.PHONY: help
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

vendor: composer.lock ## install composer deps
	composer install $(COMPOSER_INSTALL_FLAGS)
	@touch $@
composer.lock: composer.json
	composer update $(COMPOSER_UPDATE_FLAGS)
	@touch $@

.PHONY: tests-ci tests
tests-ci: composer-validate phpstan phpunit
tests: tests-ci cs-check

.PHONY: composer-validate
composer-validate: vendor composer.json composer.lock ## Validate composer.json file
	composer validate

.PHONY: phpstan
phpstan: vendor ## Check PHP code style
	vendor/bin/phpstan analyse -l7 -- src tests

.PHONY: phpunit
phpunit: vendor ## Run PhpUnit tests
	vendor/bin/phpunit -v --testdox

.PHONY: php-cs-fixer-check
cs-check: vendor ## Check php code style
	vendor/bin/php-cs-fixer fix --diff --dry-run --no-interaction -v --cache-file=.php_cs.cache --stop-on-violation

.PHONY: vendor cs-fix
cs-fix: ## Automatically php code style
	vendor/bin/php-cs-fixer fix -v --cache-file=.php_cs.cache
