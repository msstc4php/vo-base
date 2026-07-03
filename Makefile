.DEFAULT_GOAL := check
.PHONY: check fix test phpstan cs cs-fix rector rector-fix deptrac

check: phpstan cs rector deptrac test

fix: cs-fix rector-fix

test:
	vendor/bin/phpunit

phpstan:
	vendor/bin/phpstan analyse --no-progress

cs:
	vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix:
	vendor/bin/php-cs-fixer fix

rector:
	vendor/bin/rector process --dry-run

rector-fix:
	vendor/bin/rector process

deptrac:
	vendor/bin/deptrac analyse --no-progress
