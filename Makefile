.PHONY: up build down migrate fixtures exec e2e logs

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

exec:
	docker compose exec --user app php bash

e2e:
	docker compose --profile e2e run --rm e2e

logs:
	docker compose logs -f
