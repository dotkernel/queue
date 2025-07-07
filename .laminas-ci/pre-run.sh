JOB=$3
PHP_VERSION=$4
COMMAND=$(echo "${JOB}" | jq -r '.command')

echo "Running pre-run  $COMMAND"

apt-get install -y php-dev php-pear
pecl install redis
echo "extension=redis.so" | sudo tee -a /etc/php/${PHP_VERSION}/cli/php.ini
