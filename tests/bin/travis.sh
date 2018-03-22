#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# composer install fails in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

    curl -s https://getcomposer.org/installer | php

    php composer.phar install --no-interaction

    export PATH="$HOME/.composer/vendor/bin:$PATH"

    if [[ ${TRAVIS_PHP_VERSION} < 5.6 ]]; then
      composer require "phpunit/phpunit=4.8.*"
    else
      composer require "phpunit/phpunit=5.7.*"
    fi

    # install php-coveralls to send coverage info
    composer require satooshi/php-coveralls --dev

fi
