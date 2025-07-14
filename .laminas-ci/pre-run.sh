#!/usr/bin/env bash

set -e

PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')

if [[ "$PHP_VERSION" == "8.2" ]]; then
    pecl install swoole-5.0.3
elif [[ "$PHP_VERSION" == "8.3" ]]; then
    pecl install swoole-5.1.0
else

    echo "Unsupported PHP version: $PHP_VERSION"
    exit 1
fi

echo "extension=swoole.so" >> "$(php -r 'echo php_ini_loaded_file();')"
