# Frontend

Pas de bundler, pas de Turbo, pas de framework JS — uniquement [AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html), [Stimulus](https://stimulus.hotwired.dev/) et [Tailwind v4](https://tailwindcss.com/). Tout passe par des modules ES natifs servis via `<script type="importmap">`.

## Entry point

`assets/app.js` est l'unique entrée, déclarée comme `entrypoint` dans `importmap.php`. Il importe :

- `./stimulus_bootstrap.js` — qui amorce Stimulus et déclenche l'auto-discovery des controllers via `@symfony/stimulus-bundle`.
- `./styles/app.css` — point d'entrée Tailwind + imports des composants CSS custom.

L'`importmap.php` recense les dépendances (Stimulus, Turbo — installé mais non utilisé, `modern-screenshot` pour l'export image, `emoji-picker-element`).

## Controllers Stimulus

Tous dans `assets/controllers/`. L'auto-discovery du `StimulusBundle` les enregistre sous le nom `bingo_cell`, `bingo_board`, etc.

| Controller                         | Rôle                                                                                                       |
|------------------------------------|------------------------------------------------------------------------------------------------------------|
| `bingo_cell_controller.js`         | Toggle d'une case (`fetch POST /bingo/{id}/check`) ; dispatch `bingo-cell:updated` avec la réponse JSON.    |
| `bingo_board_controller.js`        | Écoute `bingo-cell:updated`, met à jour compteurs, ring SVG, halos `.in-line`, déclenche les confettis. Gère aussi le mode édition mobile (`.is-editing`). |
| `bingo_export_controller.js`       | Export d'une grille en image via `modern-screenshot`.                                                      |
| `modal_controller.js`              | Générique `<dialog>` : `open` / `close` / `backdropClose` + chargement XHR pour l'édition de case (`edit`) et soumission XHR avec swap de la cellule (`submit`). |
| `csrf_protection_controller.js`    | Injecte le token CSRF dans les formulaires (livré par défaut par Symfony).                                  |
| `emoji_picker_controller.js`       | Encapsule `emoji-picker-element`.                                                                          |
| `clipboard_controller.js`          | Copie dans le presse-papier (boutons « copier le lien »).                                                  |
| `confirm_controller.js`            | Confirmation native avant action destructive.                                                              |
| `form_controller.js`               | Soumission XHR générique (utilisé pour le formulaire d'inscription).                                       |
| `pwa_install_controller.js`        | Affiche le bouton d'installation PWA quand `beforeinstallprompt` se déclenche.                              |
| `pwa_update_controller.js`         | Toast « nouvelle version disponible » + envoi de `SKIP_WAITING` au service worker.                        |
| `tweaks_controller.js`             | Bascule palette / densité / filtre photo dans le panneau préférences (POST vers `/preferences`).           |
| `visibility_controller.js`         | Toggle public / privé d'un bingo (POST `/bingo/{slug}/visibility`).                                        |

### Event bus interne

Le couplage entre `bingo_cell`, `modal` et `bingo_board` se fait par événements custom Stimulus, jamais par référence directe :

```
bingo_cell  ──dispatch──▶ "bingo-cell:updated" ──listen──▶ bingo_board#sync
modal#submit ──dispatch──▶ "bingo-cell:updated" ──listen──▶ bingo_board#sync
```

`bingo_board#sync` reçoit `{ linePositions, completedLines, completed, total, becameDone, x, y }` et est l'unique endroit qui mute l'affichage du tableau de bord.

## Mode édition mobile

Sur mobile, le hover n'a pas de sens. Un bouton « Modifier les cases » (`bingo_board#editToggle`) bascule la classe `.is-editing` sur l'élément racine. Quand cette classe est présente, `bingo_cell#toggle` ne POST pas l'endpoint check : il délègue au bouton crayon (`data-action*="modal#edit"`) qui ouvre la modale d'édition. Voir `assets/controllers/bingo_cell_controller.js` (lignes 7-12).

## CSS

Architecture en cascade :

```
assets/styles/app.css
├── @import "tailwindcss"
├── @import "./components/bingo.css"        (la grille, les cases, les halos, l'anneau)
├── @import "./components/btn.css"          (les boutons)
├── @import "./components/switch.css"       (toggles)
├── @import "./components/emoji-picker.css"
└── @import "./components/palette.css"      (variations par data-palette)
```

### Tokens Tailwind custom

Définis dans le bloc `@theme` de `assets/styles/app.css` :

- Couleurs pastel : `--color-cream`, `--color-paper`, `--color-lavender`, `--color-peach`, `--color-mint`, `--color-sky`, `--color-butter`.
- Encre : `--color-ink`, `--color-ink-soft`, `--color-ink-faint`.
- Accent : `--color-accent`, `--color-accent-soft` — overrides par `[data-palette="ciel"]`, etc. dans `palette.css`.
- Densité : `--cell-gap`, `--cell-padding` — overrides par `[data-density="compact"]` / `comfy`.
- Typographies : `--font-sans` (Plus Jakarta Sans), `--font-serif` (Instrument Serif), `--font-hand` (Caveat).
- Radius et ombres : `--radius-card`, `--shadow-card`, `--shadow-pop`.
- Animations : `--animate-pop-in`, `--animate-line-glow`.

### Conventions sur les cases

- Tonalité visuelle cyclée via `data-tone="{{ item.position % 5 }}"` dans `templates/bingo/_cell.html.twig`.
- État coché → classe `.done`.
- Appartenance à une ligne ou colonne complète → classe `.in-line` (déclenche l'animation glow).
- Le burst de confettis est rendu dans un `.confetti-piece` injecté dans le `[data-bingo-board-target="confettiLayer"]`.

## Templates Twig

```
templates/
├── base.html.twig                        # layout principal + manifest + icônes PWA
├── home.html.twig                        # dashboard authentifié
├── landing.html.twig                     # landing publique
├── bingo/
│   ├── index.html.twig                   # grille interactive
│   ├── share.html.twig                   # grille publique (read-only)
│   ├── trash.html.twig                   # corbeille
│   ├── _grid.html.twig                   # rendu de la grille
│   └── _cell.html.twig                   # une cellule (réutilisé par le swap XHR)
├── bingo_item/
│   └── edit.html.twig                    # formulaire d'édition (rendu plein + modale)
├── components/
│   ├── header.html.twig
│   ├── new-bingo-modal.html.twig
│   ├── edit-bingo-item-modal.html.twig
│   ├── bingo-card-preview.html.twig
│   ├── _share_panel.html.twig
│   └── _tweaks.html.twig
├── registration/
└── security/
```

Les partials préfixés `_` (`_cell.html.twig`, `_grid.html.twig`) sont importés dans d'autres templates ; les composants dans `components/` sont des fragments réutilisables avec leur propre `<dialog>` ou layout.

## PWA

- `public/manifest.json` — nom, icônes (référencent `/pwa/icon-*.png`), thème pastel.
- `public/sw.js` — service worker versionné via la constante `VERSION` (à bumper à chaque modif du SHELL ou du SW lui-même).
- `public/offline.html` — page servie en fallback navigation.

Le SW applique trois stratégies de cache :

- **Navigation HTML** : network-first. Les vues publiques (`/b/*`) sont mises en cache au passage.
- **Ressources mutables** (`/pwa/*`, `/logo.png`, `/manifest.json`) : stale-while-revalidate. Pas besoin de bumper `VERSION` pour les rafraîchir.
- **App shell immuable** (`/assets/*`, `/uploads/*`) : cache-first. Les assets AssetMapper sont hashés donc sûrs à mettre en cache long terme.

> **Important** — Les icônes PWA vivent dans `public/pwa/` et **pas** dans `public/icons/`. L'hébergeur de prod (Apache mutualisé) a un `Alias /icons/` vers ses propres icônes d'auto-index qui masque tout dossier du même nom. Voir [`docs/deployment.md`](deployment.md) et le commentaire en haut de `public/sw.js`.

L'activation d'une nouvelle version SW ne fait pas `skipWaiting()` automatiquement : `pwa_update_controller.js` détecte le SW en attente, affiche un toast, et envoie le message `SKIP_WAITING` quand l'utilisateur accepte.
