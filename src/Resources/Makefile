up: docker-up
init: docker-down-clear docker-pull docker-build docker-up defaultProjectName-init
test: defaultProjectName-test

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down --remove-orphans

docker-down-clear:
	docker-compose down -v --remove-orphans

docker-pull:
	docker-compose pull

docker-build:
	docker-compose build

defaultProjectName-init: defaultProjectName-composer-install

defaultProjectName-composer-install:
	docker-compose run --rm defaultProjectName-php-cli composer install

defaultProjectName-test:
	docker-compose run --rm defaultProjectName-php-cli php bin/phpunit
