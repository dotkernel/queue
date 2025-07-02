# Queue

> [!IMPORTANT]
> Dotkernel component used to queue tasks to be processed asynchronously based on [netglue/laminas-messenger](https://github.com/netglue/laminas-messenger)

## Badges

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/queue)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/queue/main)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/queue)](https://github.com/dotkernel/queue/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/queue)](https://github.com/dotkernel/queue/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/queue)](https://github.com/dotkernel/queue/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/queue)](https://github.com/dotkernel/queue/blob/main/LICENSE.md)

[![Build Status](https://github.com/mezzio/mezzio-skeleton/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/mezzio/mezzio-skeleton/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/queue/graph/badge.svg?token=pexSf4wIhc)](https://codecov.io/gh/dotkernel/queue)
[![Qodana](https://github.com/dotkernel/queue/actions/workflows/qodana_code_quality.yml/badge.svg?branch=main)](https://github.com/dotkernel/queue/actions/workflows/qodana_code_quality.yml)
[![PHPStan](https://github.com/dotkernel/queue/actions/workflows/static-analysis.yml/badge.svg?branch=main)](https://github.com/dotkernel/queue/actions/workflows/static-analysis.yml)

## Installation

Install `dotkernel/queue` by executing the following Composer command:

```shell
composer require dotkernel/queue
```

## Setup

In order to setup the server run the following commands:

Install Valkey
```shell
sudo dnf install valkey
```

Start Valkey service
```shell
sudo systemctl start valkey
```

Enable Valkey to start automatically at system boot.
```shell
sudo systemctl enable valkey
```

Install php-redis extension, which allows PHP to communicate with Valkey/Redis.
```shell
sudo dnf install php-redis
```

Restart Apache HTTP Server and PHP-FPM
```shell
sudo systemctl restart httpd
sudo systemctl restart php-fpm
```

Check whether the redis extension is loaded in PHP
```shell
php -m | grep redis
```

Install build tools and development packages
```shell
sudo dnf install php-devel php-pear gcc make
```

Install the Brotli compression library and its development headers
```shell
sudo dnf install brotli brotli-devel
```

Check if pkg-config can locate the libbrotlienc library
```shell
pkg-config --libs libbrotlienc
```

Ensure pkg-config can find .pc config files by updating the environment variable with the correct path
```shell
export PKG_CONFIG_PATH=/usr/lib64/pkgconfig:$PKG_CONFIG_PATH
```

Install the Swoole PHP extension via PECL
> Note: PECL options should be left default.
```shell
sudo pecl install swoole
```

Navigate to PHP’s config files
```shell
cd /etc/php.d/
```

Create a new config file for the swoole extension.
```shell
sudo touch 60-swoole.ini
```

Open the file in your preferred editor
```shell
sudo nano 60-swoole.ini
```

Add the following extension and save
```shell
extension=swoole.so
```

Restart services
```shell
sudo systemctl restart php-fpm
sudo systemctl restart httpd
```
Check if swoole extension is loaded in PHP
```shell
php -m | grep swoole
```

## Usage

In order to start or stop the swoole server to you can run the following commands
```shell
php bin/cli.php swoole:start
```
```shell
php bin/cli.php swoole:stop
```

In order to start the messenger server run the following command
```shell
php bin/cli.php messenger:start
```

To test if everything is working properly you can simulate sending a message via TCP by running the following command
```shell
echo "Hello" | socat - TCP:localhost:8556
```

## Documentation

Documentation is available at: https://docs.dotkernel.org/queue-documentation
