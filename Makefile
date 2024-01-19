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
.PHONY: start

get-messages:
	symfony run -d --watch=config,src,templates,vendor symfony console messenger:consume async -vv
.PHONY: start

stop:
	symfony server:stop
	docker-compose down
.PHONY: stop

clear_homepage_cache:
	curl -s -I -X PURGE -u admin:admin `symfony var:export SYMFONY_PROJECT_DEFAULT_ROUTE_URL`admin/http-cache/
	curl -s -I -X PURGE -u admin:admin `symfony var:export SYMFONY_PROJECT_DEFAULT_ROUTE_URL`admin/http-cache/conference_header
.PHONY: clear_homepage_cache

clear_cache:
	rm -R var/cache
.PHONY: clear_cache
