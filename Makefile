.PHONY: stan cs cbf decache doc help up

default: help

help: ## Help
	@grep -E '^[a-zA-Z_-]+:.*?##.*$$' $(MAKEFILE_LIST) | sort | awk '{split($$0, a, ":"); printf "\033[36m%-30s\033[0m %-30s %s\n", a[1], a[2], a[3]}'

stan: ## Runs phpstan.
	php vendor/bin/phpstan analyse --memory-limit=-1

cs: ## Runs phpcs.
	php vendor/bin/phpcs app

cbf: ## Runs phpcbf.
	php vendor/bin/phpcbf app

decache: ## Removes all cached files.
	rm -rf temp/cache/*
	rm -rf temp/sessions/*

up: ## Start PHP server.
	php -S localhost:8080 -t www/ &