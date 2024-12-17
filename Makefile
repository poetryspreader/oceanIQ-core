# Makefile

migrate:
	php bin/console doctrine:migrations:migrate --no-interaction
start:
	php -S 127.0.0.1:8000 -t public
diff:
	php bin/console doctrine:migrations:diff