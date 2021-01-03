help: ## Shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

cs-fix: ## Run php-cs-fixer
	php tools/php-cs-fixer/vendor/bin/php-cs-fixer fix
cs-fix-diff: ## Run php-cs-fixer
	php tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --diff --dry-run
