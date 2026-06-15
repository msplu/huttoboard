#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	if grep -q DATABASE_URL .env 2>/dev/null; then
		echo 'Attente de la base de données...'
		ATTEMPTS_LEFT_TO_REACH_DATABASE=60
		until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || DATABASE_ERROR=$(php bin/console dbal:run-sql -q "SELECT 1" 2>&1); do
			if [ $? -eq 255 ]; then
				# bin/console est cassé : inutile d'insister
				break
			fi
			sleep 1
			ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
			echo "Base indisponible : $ATTEMPTS_LEFT_TO_REACH_DATABASE tentatives restantes..."
		done

		if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
			echo "$DATABASE_ERROR"
			echo "La base de données est restée injoignable, arrêt."
			exit 1
		fi

		echo 'La base de données est prête.'

		if ls -A 'migrations/' 2>/dev/null | grep -q '\.php$'; then
			php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
		fi
	fi

	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var || true
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var || true
fi

exec docker-php-entrypoint "$@"
