# My composer plugin

Configure symfony application

```shell script
# https://symfony.com/download
symfony new --full app && \
cd app && \
composer require "php:^7.4" && \
composer update --with-all-dependencies && \
bin/phpunit && \
composer configure --no-backup && \
vendor/bin/php-cs-fixer fix && \
vendor/bin/phpstan analyze && \
git add --all && \
git commit -m "Init"
```
