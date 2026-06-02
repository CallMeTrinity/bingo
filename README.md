# Bingo

> Un carnet pastel pour transformer tes envies en grille de défis. Crée ton bingo, coche tes cases, vise les lignes.

Application web qui permet de créer des grilles de bingo personnelles (envies, défis, résolutions…), de cocher les cases au fil de l'eau et de fêter les lignes complétées. Pensée mobile-first, avec un thème pastel, plusieurs palettes et un mode PWA hors-ligne.

## Fonctionnalités

- **Grilles personnalisables** : titre, année, taille de grille (4×4 par défaut).
- **Cases enrichies** : libellé, note, emoji, photo (upload via Vich Uploader).
- **Détection live** des lignes/colonnes complétées et de la condition « bingo ! », côté serveur (`BingoChecker`).
- **Toggle instantané** des cases via Stimulus + JSON (pas de rechargement, pas de Turbo).
- **Partage en lecture seule** d'un bingo via une URL publique (`/b/{slug}`).
- **Corbeille** : suppression douce (`deletedAt`), restauration ou suppression définitive.
- **Comptes utilisateurs** avec préférences (palette, densité d'affichage, filtre photo).
- **PWA** : manifest, service worker, page hors-ligne.
- **i18n** : interface en français.

## Stack

- **Backend** : PHP 8.4 · Symfony 8 · Doctrine ORM 3
- **Frontend** : AssetMapper (sans bundler) · Stimulus · Twig
- **Styling** : Tailwind v4 via `symfonycasts/tailwind-bundle` + CSS custom
- **Base de données** : MariaDB (via Docker Compose)
- **Tests** : PHPUnit 13

## Prérequis

- PHP **8.4+** avec extensions `ctype` et `iconv`
- Composer
- [Symfony CLI](https://symfony.com/download)
- Docker (pour MariaDB)

## Démarrage rapide

```bash
# 1. Installer les dépendances PHP
composer install

# 2. Démarrer la base de données
docker compose up -d

# 3. Lancer les migrations
php bin/console doctrine:migrations:migrate

# 4. (Optionnel) Charger les fixtures de dev
php bin/console doctrine:fixtures:load

# 5. Lancer le watcher Tailwind dans un terminal
php bin/console tailwind:build --watch

# 6. Lancer le serveur Symfony dans un autre terminal
symfony server:start
```

L'application est disponible sur [https://127.0.0.1:8000](https://127.0.0.1:8000).

## Commandes utiles

```bash
# Lancer toute la suite de tests
php bin/phpunit

# Lancer un test précis
php bin/phpunit tests/path/to/TestFile.php

# Générer une migration après modification d'entité
php bin/console doctrine:migrations:diff

# Régénérer le CSS Tailwind (one-shot)
php bin/console tailwind:build
```

## Architecture

### Modèle de données

- **`User`** : email, mot de passe, displayName, préférences (palette, densité, filtre photo) et bingos possédés.
- **`Bingo`** : `title`, `year`, `slug` (8 caractères hex auto-générés en `PrePersist`), `size` (dimension de la grille, 4 par défaut), `isPublic`, `deletedAt` (suppression douce) et la collection de `BingoItem`.
- **`BingoItem`** : `label`, `position` (entier 1-based), `completedAt`, `note`, `emoji`, `imageName` (photo via Vich). Rattachée à un `Bingo`.

Les cases sont adressées par position : ligne `r`, colonne `c` → position `r * size + c + 1`.

### Routes principales

| Méthode    | URL                          | Nom                | Description                              |
|------------|------------------------------|--------------------|------------------------------------------|
| GET        | `/`                          | `app_home`         | Landing publique (redirige si connecté)  |
| GET / POST | `/mes-bingos`                | `app_dashboard`    | Liste + création de bingo                |
| GET        | `/bingo/{slug}`              | `bingo_show`       | Grille interactive (propriétaire)        |
| GET        | `/b/{slug}`                  | `bingo_share`      | Grille publique en lecture seule         |
| POST       | `/bingo/{id}/check`          | `bingo_item_check` | Toggle d'une case (réponse JSON)         |
| GET / POST | `/bingo/item/{id}/edit`      | —                  | Édition modale d'une case                |
| POST       | `/bingo/{slug}/visibility`   | `bingo_visibility` | Bascule public / privé                   |
| POST       | `/bingo/{slug}/delete`       | `bingo_delete`     | Envoi à la corbeille                     |
| POST       | `/bingo/{slug}/restore`      | `bingo_restore`    | Restauration depuis la corbeille         |
| POST       | `/bingo/{slug}/destroy`      | `bingo_destroy`    | Suppression définitive                   |
| GET        | `/corbeille`                 | `bingo_trash`      | Corbeille de l'utilisateur               |

Les URL utilisent le `slug` du bingo (jamais l'`id` ni l'`year`, car plusieurs bingos peuvent partager une même année).

### Service `BingoChecker`

`src/Service/BingoChecker.php` calcule côté serveur les lignes/colonnes complétées et la condition « bingo ». Il est *size-aware* (utilise `Bingo::getSize()`) et renvoie des tableaux de positions. Utilisé à la fois lors du rendu de la page et dans la réponse JSON du toggle.

### Flux côté client

Le toggle de case est purement JS → JSON, sans rechargement ni Turbo Frames. Le Stimulus `bingo_board_controller` consomme la réponse JSON (`linePositions`, `completedLines`, `completed`, `total`) et met à jour les compteurs, l'anneau de progression, le calque confetti et les halos sur les lignes/colonnes complétées.

### Contrôleurs Stimulus

`assets/controllers/` :

- `bingo_cell_controller.js` — toggle d'une case (POST + dispatch `bingo-cell:updated`)
- `bingo_board_controller.js` — réagit à l'événement et anime le tableau
- `bingo_export_controller.js` — export de grille
- `modal_controller.js` — ouverture / fermeture des `<dialog>`
- `emoji_picker_controller.js`, `clipboard_controller.js`, `confirm_controller.js`, `form_controller.js`, `tweaks_controller.js`, `visibility_controller.js`, `csrf_protection_controller.js`

### Styles

Les styles vivent dans `assets/styles/app.css` et `assets/styles/components/bingo.css`. Les tokens custom Tailwind (`--color-lavender`, `--color-peach`, etc.) y sont définis. Les tonalités des cases cyclent via `data-tone="{{ item.position % 5 }}"`.

## Structure du projet

```
bingo/
├── assets/            # JS (Stimulus) + CSS (Tailwind + custom)
├── bin/               # console, phpunit
├── config/            # config Symfony
├── migrations/        # migrations Doctrine
├── public/            # docroot (index.php, manifest, sw.js, uploads/)
├── src/
│   ├── Controller/    # Bingo, BingoItem, Preferences, Registration, Security
│   ├── DataFixtures/
│   ├── Entity/        # Bingo, BingoItem, User
│   ├── Form/
│   ├── Repository/
│   ├── Security/      # Voters
│   └── Service/       # BingoChecker
├── templates/         # Twig
├── tests/             # PHPUnit
└── translations/
```

## Déploiement

Un script `deploy.sh` est fourni à la racine du projet.

## Licence

Propriétaire — voir `composer.json`.
