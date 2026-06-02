#!/bin/bash
set -e

git fetch --tags
# shellcheck disable=SC2046
git checkout $(git tag --sort=-version:refname | head -1)
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console tailwind:build --minify
php bin/console asset-map:compile
php bin/console cache:clear --env=prod
echo "Deploy terminé."
