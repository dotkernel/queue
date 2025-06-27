#!/bin/bash

PHP_VERSION="$1"
apt install make
pecl install swoole
echo "extension=swoole.so" > /etc/php/${PHP_VERSION}/mods-available/60-swoole.ini
phpenmod -v ${PHP} -s cli swoole
