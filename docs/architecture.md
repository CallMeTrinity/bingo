# Architecture

## Vue d'ensemble

Application Symfony 8 monolithique. Le backend rend les vues Twig et expose quelques endpoints JSON pour les interactions sans rechargement. Pas de SPA, pas de Turbo : le seul mouvement « live » est le toggle de case (fetch + JSON + DOM update via Stimulus).

```
Browser ──HTML──▶ Twig (Symfony)
   │                │
   │                ├── BingoChecker (calcul des lignes/colonnes)
   │                └── Doctrine ORM ──▶ MariaDB
   │
   └──fetch POST──▶ BingoItemController::check ──JSON──▶ Stimulus board
```

## Modèle de données

Trois entités principales, toutes dans `src/Entity/`.

### `User` (`src/Entity/User.php`)

Implémente `UserInterface` + `PasswordAuthenticatedUserInterface`.

| Champ          | Type                       | Note                                                          |
|----------------|----------------------------|---------------------------------------------------------------|
| `email`        | `string(180)`, unique      | Identifiant Symfony Security                                  |
| `password`     | `string`                   | Hash                                                          |
| `displayName`  | `string(80)`, nullable     |                                                               |
| `roles`        | `json`                     | `ROLE_USER` ajouté implicitement par `getRoles()`             |
| `preferences`  | `json`                     | Stockage libre — palette, densité, filtre photo               |
| `createdAt`    | `DateTimeImmutable`        | Renseigné en `PrePersist` (`User.php:166`)                    |
| `bingos`       | `OneToMany → Bingo`        |                                                               |

Les préférences UI (palette / densité / filtre photo) sont validées via des accesseurs (`setPalette`, `setDensity`) qui rejettent les valeurs hors enum (`User::PALETTES`, `User::DENSITIES`).

### `Bingo` (`src/Entity/Bingo.php`)

| Champ        | Type                          | Note                                                   |
|--------------|-------------------------------|--------------------------------------------------------|
| `title`      | `string(255)`                 | Requis                                                 |
| `year`       | `int`                         | 1900 – 2100                                            |
| `slug`       | `string(8)`, unique           | Hex 8 caractères, généré en `PrePersist` (`Bingo.php:128`) via `bin2hex(random_bytes(4))` |
| `size`       | `int`, défaut `4`             | Dimension de la grille (3, 4 ou 5)                     |
| `isPublic`   | `bool`, défaut `false`        | Conditionne l'accès à `/b/{slug}`                      |
| `deletedAt`  | `DateTimeImmutable`, nullable | Soft delete (corbeille)                                |
| `owner`      | `ManyToOne → User`            | Non nullable                                           |
| `bingoItems` | `OneToMany → BingoItem`       | `cascade: ['persist', 'remove']`                       |

Le slug auto-généré sert d'identifiant public stable : il est utilisé dans toutes les URLs (`/bingo/{slug}`, `/b/{slug}`), jamais l'`id` ni l'`year`. Deux bingos peuvent partager une même année.

### `BingoItem` (`src/Entity/BingoItem.php`)

| Champ         | Type                          | Note                                                  |
|---------------|-------------------------------|-------------------------------------------------------|
| `bingo`       | `ManyToOne → Bingo`           |                                                       |
| `label`       | `string(255)`, nullable       | Texte de la case (peut être vide si emoji/image seul) |
| `position`    | `int`                         | 1-based, `r * size + c + 1`                           |
| `completedAt` | `DateTimeImmutable`, nullable | `null` = non cochée                                   |
| `note`        | `text`, nullable              |                                                       |
| `emoji`       | `string(16)`, nullable        |                                                       |
| `imageName`   | `string(255)`, nullable       | Géré par VichUploaderBundle (mapping `bingo_item_image`) |

Adressage des cellules : pour une grille de taille `size`, la case en ligne `r` (0-indexée) et colonne `c` (0-indexée) a pour position `r * size + c + 1`. La position commence à 1, pas à 0.

## Routing

Toutes les routes sont déclarées en attributs PHP sur les contrôleurs (`config/routes.yaml` se contente d'importer `routing.controllers`). Les routes `/bingo/{slug}/*` opèrent sur le slug ; seule la route de toggle utilise l'`id` de l'item (`/bingo/{id}/check`).

| Méthode     | URL                        | Contrôleur                                       | Description                          |
|-------------|----------------------------|--------------------------------------------------|--------------------------------------|
| `GET`       | `/`                        | `BingoController::home`                          | Landing publique (redirige si connecté) |
| `GET\|POST` | `/mes-bingos`              | `BingoController::dashboard`                     | Liste + création                     |
| `GET`       | `/bingo/{slug}`            | `BingoController::index`                         | Grille interactive                   |
| `GET`       | `/b/{slug}`                | `BingoController::share`                         | Vue publique (`isPublic`)            |
| `POST`      | `/bingo/{slug}/visibility` | `BingoController::visibility`                    | Toggle public / privé (JSON)         |
| `POST`      | `/bingo/{slug}/delete`     | `BingoController::delete`                        | Soft delete                          |
| `POST`      | `/bingo/{slug}/restore`    | `BingoController::restore`                       | Restauration                         |
| `POST`      | `/bingo/{slug}/destroy`    | `BingoController::destroy`                       | Suppression définitive               |
| `GET`       | `/corbeille`               | `BingoController::trash`                         | Corbeille                            |
| `POST`      | `/bingo/{id}/check`        | `BingoItemController::check`                     | Toggle d'une case (JSON)             |
| `GET\|POST` | `/bingo/item/{id}/edit`    | `BingoItemController::edit`                      | Modale d'édition                     |
| `POST`      | `/preferences`             | `PreferencesController::update`                  | Palette / densité / filtre photo     |
| `GET\|POST` | `/login`                   | `SecurityController::login`                      |                                      |
| `GET`       | `/logout`                  | `SecurityController::logout`                     | Interceptée par le firewall          |
| `GET\|POST` | `/register`                | `RegistrationController::register`               |                                      |

## Service `BingoChecker`

`src/Service/BingoChecker.php` est le seul endroit qui calcule les lignes et colonnes complétées. Il est *size-aware* : `lines()` et `columns()` génèrent les positions à partir de `$bingo->getSize()`.

API publique :

- `getCompletedPositions(Bingo)` → `int[]` des positions cochées
- `getCompletedLines(Bingo)` → `list<list<int>>` des lignes entièrement cochées
- `getCompletedColumns(Bingo)` → idem pour les colonnes
- `getLinePositions(Bingo)` → `int[]` union des positions appartenant à une ligne ou colonne complète (utilisé pour les halos CSS)
- `hasBingo(Bingo)` → `bool`, vrai dès qu'une ligne **ou** colonne est complète

Les diagonales ne sont **pas** détectées. Utilisé à la fois lors du rendu de page et dans la réponse JSON du toggle (`BingoController::index` à `BingoController.php:164`, `BingoItemController::check` à `BingoItemController.php:17`).

## Flux côté client : toggle de case

1. L'utilisateur tape sur une case → `bingo_cell_controller#toggle` (`assets/controllers/bingo_cell_controller.js`).
2. `fetch('/bingo/{id}/check', { method: 'POST' })` — pas de body, pas de CSRF (route mutante mais sans token car le voter contrôle déjà l'accès).
3. Le contrôleur Symfony bascule `completedAt`, persiste, recalcule lignes / colonnes via `BingoChecker`, renvoie :
   ```json
   {
     "active": true,
     "linePositions": [1, 2, 3, 4],
     "completedLines": 1,
     "completed": 7,
     "total": 16
   }
   ```
4. Le controller Stimulus dispatch un `bingo-cell:updated` ; le `bingo_board_controller` met à jour les compteurs, l'anneau de progression SVG, les halos `.in-line` et déclenche un burst de confettis si la case vient d'être cochée.

Aucun rechargement, aucun Turbo Frame, aucun Stream HTML. La cohérence est garantie par le fait que le serveur reste l'unique source de vérité (calcul des lignes inclus).

## Flux édition de cellule

`BingoItemController::edit` est dual :

- Requête GET « normale » → rend `bingo_item/edit.html.twig` (utilisé en mobile via le mode édition).
- Requête XHR (fetch depuis `modal_controller#edit`) → retourne le même HTML mais consommé pour remplir une `<dialog>`.
- Submit XHR → retourne du JSON contenant `cellHtml` (HTML rendu de la cellule, `templates/bingo/_cell.html.twig`) + `stats`. Le `modal_controller` remplace la cellule via `outerHTML` et dispatche `bingo-cell:updated` pour mettre à jour le tableau de bord.

## Sécurité

- **Firewall** : `config/packages/security.yaml` (form login standard + remember-me selon config).
- **Voter** : `BingoVoter` (`src/Security/Voter/BingoVoter.php`) gère deux attributs :
  - `BINGO_EDIT` — autorisé uniquement si `user === bingo.owner`.
  - `BINGO_VIEW` — délègue à `BINGO_EDIT` (la vue publique `/b/{slug}` court-circuite le voter et vérifie `isPublic` directement).
- **CSRF** : toutes les actions de mutation par formulaire (`delete`, `restore`, `destroy`, `visibility`, `preferences`) vérifient un token. Le toggle et le edit de case ne demandent pas de token CSRF — l'authentification + voter suffisent.

## Repositories utiles

`src/Repository/BingoRepository.php` expose les requêtes filtrées par owner et par état corbeille :

- `findActiveForOwner(User)` — bingos non supprimés, triés par année puis id descendant
- `findTrashed(User)` / `countTrashed(User)`
- `findOneActiveBySlug(string)` / `findOneTrashedBySlug(string)`

Tous les contrôleurs passent par ces méthodes pour garantir la cohérence du soft delete.
