JOB=$3
PHP_VERSION=$4
COMMAND=$(echo "${JOB}" | jq -r '.command')

echo "Running pre-run  $COMMAND"

apt-get install php"${PHP_VERSION}"--php-redis
