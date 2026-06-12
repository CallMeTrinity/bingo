# Développement

## Mise en route locale

Prérequis : PHP 8.4+, Composer, [Symfony CLI](https://symfony.com/download), Docker.

```bash
# 1. Dépendances
composer install

# 2. Base de données (MariaDB + Mailpit pour le mailer en dev)
docker compose up -d

# 3. Migrations
php bin/console doctrine:migrations:migrate

# 4. (Optionnel) Fixtures
php bin/console doctrine:fixtures:load

# 5. Tailwind en watch dans un terminal
php bin/console tailwind:build --watch

# 6. Serveur Symfony dans un autre terminal
symfony server:start
```

Le `compose.override.yaml` expose MariaDB sur `localhost:3306` et démarre [Mailpit](https://github.com/axllent/mailpit) (interface web sur un port mappé dynamiquement, voir `docker compose ps`).

## Variables d'environnement

Les valeurs par défaut (`.env`) ciblent le service Docker local :

- `DATABASE_URL="mysql://db:db@127.0.0.1:3306/db?serverVersion=11.4.4-MariaDB&charset=utf8mb4"`
- `MAILER_DSN=null://null` (override en local pour pointer vers Mailpit si besoin)
- `MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0`
- `APP_SHARE_DIR=var/share`

Pour overrider sans toucher au fichier versionné : créer un `.env.local`.

## Doctrine

### Créer une migration

Après modification d'entité :

```bash
php bin/console doctrine:migrations:diff
```

Inspecter le fichier généré dans `migrations/` (typiquement `VersionYYYYMMDDHHMMSS.php`) puis appliquer :

```bash
php bin/console doctrine:migrations:migrate
```

### Fixtures

`src/DataFixtures/UserFixtures.php` et `src/DataFixtures/BingoFixtures.php` peuplent quelques utilisateurs et bingos de démonstration. À ne **jamais** lancer en prod (l'autoloader limite leur chargement à l'env dev / test, mais la commande `doctrine:fixtures:load` tronque les tables avant insertion).

```bash
php bin/console doctrine:fixtures:load          # avec confirmation
php bin/console doctrine:fixtures:load --no-interaction
```

## Tailwind

Le bundle `symfonycasts/tailwind-bundle` lit `assets/styles/app.css` (qui importe `tailwindcss` et les composants custom) et écrit dans `var/tailwind/`.

- **Dev** : `php bin/console tailwind:build --watch` (recompile à chaque sauvegarde des templates et CSS).
- **Build one-shot** : `php bin/console tailwind:build`.
- **Prod** : `php bin/console tailwind:build --minify` (lancé par `deploy.sh`).

Si une classe Tailwind n'apparaît pas dans la sortie, vérifier que le template qui l'utilise est bien scanné — le bundle suit la configuration par défaut (`templates/**/*.html.twig` + `assets/**/*.{js,css}`).

## AssetMapper

Pas de bundler. Les assets statiques sont servis directement depuis `assets/` en dev et compilés dans `public/assets/` en prod via `php bin/console asset-map:compile`.

Pour ajouter une dépendance JS depuis npm :

```bash
php bin/console importmap:require <package>
```

L'entrée est ajoutée à `importmap.php` ; les fichiers vendor sont téléchargés dans `assets/vendor/` (versionné).

> **Note** — `public/assets/` est figé en prod. En dev, s'il est présent par erreur, il masque les modifications front. Le supprimer (`rm -rf public/assets/`) ou relancer `asset-map:compile` si besoin.

## Tests

```bash
# Suite complète
php bin/phpunit

# Un fichier précis
php bin/phpunit tests/path/to/TestFile.php

# Avec couverture
php bin/phpunit --coverage-html var/coverage
```

La config PHPUnit est dans `phpunit.dist.xml`. Le bootstrap `tests/bootstrap.php` est minimal. À ce jour, peu de tests sont écrits — opportunité d'ajouter au moins un test unitaire pour `BingoChecker` et un test fonctionnel pour le flow de toggle.

## Cache Symfony

```bash
php bin/console cache:clear              # env courant (dev par défaut)
php bin/console cache:clear --env=prod
```

## Debug utile

```bash
# Lister toutes les routes
php bin/console debug:router

# Inspecter les services
php bin/console debug:container

# Voir la table de routage AssetMapper
php bin/console debug:asset-map

# Voir l'arbre des bundles activés
php bin/console debug:config
```

Le `WebProfilerBundle` est actif en dev — la toolbar en bas de page donne accès aux requêtes Doctrine, Twig, sécurité, etc.

## Conventions

- **PHP** : PSR-12, `declare(strict_types=1)` n'est pas obligatoire mais reste recommandé pour les nouvelles classes.
- **Twig** : préférer les `templates/components/` pour les fragments réutilisables et les `_*.html.twig` pour les partials internes à un dossier.
- **Stimulus** : un fichier par contrôleur dans `assets/controllers/`, suffixé `_controller.js`. L'auto-discovery du `StimulusBundle` les enregistre automatiquement — pas besoin de toucher à `controllers.json` sauf pour les contrôleurs venant de vendor.
- **Migrations** : ne jamais modifier une migration déjà mergée ; toujours créer une nouvelle.
