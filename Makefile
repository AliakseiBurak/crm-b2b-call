.PHONY: help up build down migrate fixtures styles exec e2e test logs

help:
	@echo "help up build down migrate fixtures styles exec e2e test logs"

up:
	docker compose up -d

build:
	docker compose build

down:
	docker compose down

migrate:
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction

styles:
	npm run build

exec:
	docker compose exec --user app php bash

e2e:
	docker compose --profile e2e run --rm e2e

test:
	docker compose exec --user app php php bin/phpunit

logs:
	docker compose logs -f
