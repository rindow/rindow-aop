#!/bin/sh

composer install

case "$TRAVIS_PHP_VERSION" in
	5\.6) composer require --dev "phpunit/phpunit 5.*" ;;
	7\.*) composer require --dev "phpunit/phpunit 6.*" ;;
esac
