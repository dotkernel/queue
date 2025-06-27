#!/bin/bash

JOB=$3

COMMAND=$(echo "${JOB}" | jq -r '.command // ""')
[[ "${COMMAND}" =~ ^REDIS_VERSION=([0-9\.]+) ]] || exit 0

PHP=$(echo "${JOB}" | jq -r '.php // ""')
REDIS_VERSION=${BASH_REMATCH[1]}

echo "SETUP: Installing ext-redis $REDIS_VERSION with PHP $PHP..."
pecl install -f --configureoptions 'enable-redis-igbinary="yes" enable-redis-lzf="yes"' igbinary redis-${REDIS_VERSION}

if [ $? -ne 0 ]; then
  echo "ERROR: Installation of ext-redis $REDIS_VERSION with PHP $PHP failed."
  exit 1
fi

echo "extension=redis.so" > /etc/php/${PHP}/mods-available/redis.ini
