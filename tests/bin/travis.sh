#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# composer install fails in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

    curl -s http://getcomposer.org/installer | php

    php composer.phar install --dev --no-interaction

    # install php-coveralls to send coverage info
    composer require satooshi/php-coveralls --dev

elif [ $1 == 'after' ]; then

	# no Xdebug and therefore no coverage in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

	travis_retry php vendor/bin/coveralls -v
fi
