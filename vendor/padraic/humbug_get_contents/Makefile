PHP_CS_FIXER=php -d zend.enable_gc=0 vendor-bin/php-cs-fixer/vendor/bin/php-cs-fixer
PHPUNIT=php -d zend.enable_gc=0 vendor/bin/phpunit

.DEFAULT_GOAL := help
.PHONY: test tu cs


help:
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'


##
## Tests
##---------------------------------------------------------------------------

test:           ## Run all the tests
test: tu

tu:             ## Run the tests for the core library
tu: vendor
	$(PHPUNIT)


##
## Code Style
##---------------------------------------------------------------------------

cs:             ## Run the CS Fixer
cs:	vendor
	$(PHP_CS_FIXER) fix


##
## Rules from files
##---------------------------------------------------------------------------

vendor: composer.lock
	composer install

composer.lock: composer.json
	@echo compose.lock is not up to date.
