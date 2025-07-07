JOB=$3
PHP_VERSION=$4
COMMAND=$(echo "${JOB}" | jq -r '.command')

echo "Running pre-run  $COMMAND"

apt-get install -y php"${PHP_VERSION}"-redis
