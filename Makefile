SHELL := /bin/bash

COMPOSER_INSTALL_FLAGS ?=
COMPOSER_UPDATE_FLAGS ?=

SYMFONY_DEPRECATIONS_HELPER := "max[self]=0"

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

.PHONY: tests tests-without-cs
tests-without-cs: composer-validate phpstan phpunit ## Run all tests but code style check
tests: tests-without-cs cs-check ## Run all tests

.PHONY: composer-validate
composer-validate: vendor ## Validate composer.json file
	composer validate

.PHONY: phpstan
phpstan: vendor ## Check PHP code style
	vendor/bin/phpstan analyse

.PHONY: phpunit
phpunit: vendor ## Run PhpUnit tests
	vendor/bin/phpunit -v

.PHONY: cs-check
cs-check: vendor ## Check php code style
	vendor/bin/php-cs-fixer fix --diff --dry-run --no-interaction -v --cache-file=.php_cs.cache --stop-on-violation

.PHONY: cs-fix
cs-fix: vendor ## Automatically fix php code style
	vendor/bin/php-cs-fixer fix -v --cache-file=.php_cs.cache
