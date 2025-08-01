apt-get update
apt-get install -y php-dev php-pear libcurl4-openssl-dev libssl-dev gcc make autoconf

pecl channel-update pecl.php.net

pecl install -f swoole

echo "extension=swoole.so" > /etc/php/cli/conf.d/20-swoole.ini
