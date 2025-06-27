#!/bin/bash

PHP_VERSION="$1"

apt update
apt install -y make php-dev php-pear

pecl install redis

echo "extension=redis.so" | sudo tee /etc/php/${PHP_VERSION}/mods-available/redis.ini

sudo phpenmod -v ${PHP_VERSION} -s cli redis
