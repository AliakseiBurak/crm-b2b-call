.PHONY: up down migrate fixtures e2e

up:
	docker compose up -d

down:
	docker compose down

migrate:
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction

e2e:
	docker compose --profile e2e run --rm e2e
