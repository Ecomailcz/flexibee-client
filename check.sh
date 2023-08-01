composer install
composer normalize
vendor/bin/phpcbf --standard=ruleset.xml --extensions=php --tab-width=4 -sp src tests
php -d memory_limit=8G vendor/bin/phpstan analyse src/ -c phpstan.neon
vendor/bin/parallel-lint src tests --exclude vendor
php vendor/bin/phpcs --standard=ruleset.xml --extensions=php --encoding=utf-8 src tests
vendor/bin/paratest tests
