SHELL := /bin/bash

tests:
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:migrations:migrate -n --env=test
	symfony console doctrine:fixtures:load -n --env=test
	symfony php bin/phpunit $(MAKECMDGOALS)
.PHONY: tests

start:
	symfony server:start -d
	docker-compose up -d
	symfony run -d --watch=config,src,templates,vendor symfony console messenger:consume async -vv
.PHONY: start

stop:
	symfony server:stop
	docker-compose down
.PHONY: stop
