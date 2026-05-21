#!/bin/bash
set -e

git pull
composer install --no-dev --optimize-autoloader
php bin/console tailwind:build --minify
php bin/console asset-map:compile
php bin/console cache:clear --env=prod

echo "Deploy terminé."
