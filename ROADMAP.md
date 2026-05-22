# Roadmap — Bingo Annuel

## Stack actuelle

- **Back** : Symfony 8 · Doctrine ORM · PHP 8.4
- **Front** : AssetMapper · Stimulus (auto-discovery) · pas de Turbo
- **Style** : Tailwind v4 (bundle Symfonycasts) + CSS custom · polices Google (Instrument Serif · Plus Jakarta Sans · Caveat)
- **Data flow** : POST JSON → JS Stimulus met à jour le DOM (sans Turbo Frames). Pas de full reload.

---

## ✅ Phase 1 — Foundation

Entités `Bingo` (`year`, `title`, `slug`, items) et `BingoItem` (`label`, `position`, `completedAt`, `note`), migration, fixtures, route `/bingo/{year}` qui rend la grille 4×4.

Fait : `src/Entity/{Bingo,BingoItem}.php`, `src/DataFixtures/BingoFixtures.php`, `src/Controller/BingoController.php`, `migrations/Version*`, `templates/bingo/index.html.twig`.

---

## ✅ Phase 2 — Interactivité

Toggle d'une case sans rechargement via Stimulus + `fetch` JSON (pas Turbo). Détection serveur des lignes/colonnes/diagonales complètes.

- `POST /bingo/{id}/check` → JSON `{ active, linePositions, completedLines, completed, total }`
- `BingoChecker` : `getCompletedLines`, `getCompletedColumns`, `getLinePositions`, `hasBingo`
- `bingo_cell_controller.js` : POST + toggle classe `done` + dispatch `bingo-cell:updated`
- `bingo_board_controller.js` : sync halo `in-line`, stat chips, anneau de progression

---

## ✅ Phase 3 — Design pastel

Port du design exporté depuis claude.ai/design (carnet de bonnes résolutions, pastels lavande/pêche/menthe).

- **Top bar** sticky : logo « B », titre serif, fraction `x/y` et anneau SVG de progression
- **Stat chips** : cases faites, lignes complètes, reste à faire, année
- **Cellules sticker** : background pastel cyclé via `data-tone` (5 tons), rotation `-0.6deg` quand cochée, note manuscrite (`Caveat`) qui apparaît, badge check animé en `pop-in`
- **Halo `line-glow`** persistant sur les cellules d'une ligne/colonne complète
- **Confettis** au moment où une case bascule en `done` (22 particules pastel à la position du clic)

Fichiers : `assets/styles/components/bingo.css`, `assets/controllers/bingo_{cell,board}_controller.js`, `templates/{base,bingo/index}.html.twig`.

---

## 🚧 Phase 4 — Multi-bingo (accueil + création)

**Objectif** : page d'accueil qui liste tous les bingos, bouton « Nouveau bingo ».

### 4.1 Route `/` — liste

```php
#[Route('/', name: 'home')]
public function home(BingoRepository $repo, BingoChecker $checker): Response
```

Template `templates/home.html.twig` : grille de cartes avec, pour chaque bingo :
- mini-preview de la grille (les cases done colorées via `cellTone(position)`)
- chip année, titre, sous-titre, taille
- barre de progression dégradée + `x/16 faits · y%`

Carte « + Nouveau bingo » en dashed border à la fin.

### 4.2 Création — modale ou page dédiée

Form Symfony `BingoType` : `title`, `year`, taille (3/4/5 ; voir 4.3 pour le support multi-taille). Génération du `slug` en `PrePersist` (déjà documenté dans l'entité).

À la création : générer N² `BingoItem` vides (`label = ''`, `position = 0..N²-1`). Redirige sur `/bingo/{year}` pour les remplir.

### 4.3 Taille de grille variable (optionnel mais conseillé avant 4.2)

Aujourd'hui `BingoChecker::LINES` et `COLUMNS` sont en dur pour du 4×4. Pour supporter 3/4/5 :
- Ajouter `Bingo::$size` (int, default 4), migration
- Calculer `LINES`/`COLUMNS` dynamiquement à partir de `$bingo->getSize()`
- Adapter `bingo-grid` CSS via `style="grid-template-columns: repeat({{ bingo.size }}, 1fr)"`

---

## 🚧 Phase 5 — Édition d'une case

**Objectif** : éditer le `label` et la `note` d'une case (et marquer fait/non-fait depuis la modale).

### 5.1 Route + modale

`GET /bingo/item/{id}/edit` → fragment HTML inséré dans un `<dialog>` côté front.
`POST /bingo/item/{id}` → met à jour, renvoie le partial cellule + recompute stats.

### 5.2 Modal côté Stimulus

Nouveau `cell_edit_modal_controller` qui :
- ouvre la modale au clic sur un bouton crayon visible au hover de la cellule
- contient les champs `label` (input), `note` (textarea, police Caveat), checkbox « fait »
- POST en `fetch`, remplace le HTML de la cellule par la réponse, ferme la modale

Le design original a aussi un picker emoji par case — voir Phase 7.4 si on ajoute le champ emoji à `BingoItem`.

### 5.3 Date de complétion affichée

Aujourd'hui `completedAt` est stocké mais pas affiché. Dans la cellule ou la modale :
```twig
{% if item.completedAt %}
    <time>{{ item.completedAt|date('d/m/Y') }}</time>
{% endif %}
```

---

## 🚧 Phase 6 — Partage

**Objectif** : URL publique en lecture seule + export image personnalisable.

### 6.1 Route `/b/{slug}`

```php
#[Route('/b/{slug}', name: 'bingo_share')]
```

Template `templates/bingo/share.html.twig` : même look que `index.html.twig` mais sans `data-controller="bingo-cell"` (lecture seule). Bandeau « Bingo de … — {{ year }} ».

### 6.2 Bouton de partage

Dans la top bar : bouton « Partager » → ouvre un panneau qui propose (a) copie de l'URL, (b) export image. Le partage URL passe par `clipboard_controller` Stimulus, feedback tooltip « Copié ! ».

### 6.3 Export image

**Objectif** : générer une image téléchargeable du bingo, avec options pour ajuster l'apparence avant export.

**Options** présentées dans le panneau de partage (avant clic sur « Télécharger ») :
- **Palette** : sélecteur parmi les 4 palettes pastel (Lavande, Ciel, Sorbet, Matcha) — réutilise le système CSS variables de la Phase 7.5
- **Cases cochées** : `Afficher · Masquer · Style "carnet" (line-through)` — permet d'exporter un bingo vierge à imprimer ou un bingo rempli pour partager une progression
- **Notes** : `Afficher · Masquer` — pour ne pas exposer ses notes perso
- **En-tête** : `Année + titre · Titre seul · Vide`
- **Format** : `Carré 1080×1080 (Insta) · Story 1080×1920 · A4 portrait (impression)`

**Implémentation** : génération côté client via [`html2canvas`](https://html2canvas.hertzen.com/) (ou `dom-to-image`). Un `bingo_export_controller` Stimulus :
1. Clone le `.bingo-card` dans un container offscreen avec les options appliquées
2. Capture en canvas, déclenche le download du PNG
3. Pas d'aller-retour serveur, tout reste local

Si on a besoin d'un export plus fidèle ou serveur-side plus tard : route `/b/{slug}/export.png` rendant un template dédié avec [browsershot](https://github.com/spatie/browsershot) (Chromium headless).

### 6.4 Meta Open Graph

Image OG dynamique en réutilisant la même logique d'export : route `/og/{slug}.png` qui génère un visuel 1200×630 server-side (browsershot ou rendu via le template share). À skipper au début — un `<meta og:title>` text-only suffit avant.

---

## 🚧 Phase 7 — Multi-utilisateur

**Objectif** : chaque personne a son compte et ses bingos.

### 7.1 Entité User + Security

```bash
php bin/console make:user
php bin/console make:auth
```

`User` : `id`, `email` (unique), `displayName`, `password` (hashé), `createdAt`. Provider Doctrine, authenticator form_login. Routes `/login`, `/register`, `/logout`.

### 7.2 Ownership des bingos

Migration : ajouter `Bingo::$owner` (ManyToOne User), nullable au début pour migrer les fixtures, puis NOT NULL une fois en place. `BingoRepository::findByOwner(User $u)`.

Toutes les routes `bingo_*` (sauf `bingo_share` qui reste publique via slug) filtrent par `getUser()`. Voter Symfony `BingoVoter` pour `VIEW`/`EDIT` : owner-only sur edit, public-via-slug sur view.

### 7.3 Page d'accueil par utilisateur

Phase 4 sera implémentée pensée single-user ; à cette étape on la refactore pour ne retourner que les bingos de l'utilisateur connecté. Page `/login` séparée pour les invités, redirige vers `/` une fois loggué.

### 7.4 Partage avec privacy par défaut

Les bingos sont **privés** par défaut. Ajouter `Bingo::$isPublic` (bool, default false). Le slug ne fonctionne que si `isPublic === true`. Toggle dans les paramètres du bingo.

### 7.5 Affichage du propriétaire sur la page share

`templates/bingo/share.html.twig` : « Bingo de {{ bingo.owner.displayName }} — {{ year }} » dans le bandeau.

### 7.6 Migration des données existantes

Au moment de mettre `Bingo::$owner` NOT NULL : script qui crée un user `admin` et attribue tous les bingos orphelins. À documenter dans le `README` / un `make:migration` data-only.

---

## 🚧 Phase 8 — Polish


### 8.2 PWA

`public/manifest.json` + service worker minimal pour offline. Icônes 192/512.

### 8.3 Photo-preuve par case

Champ `imageFile` sur `BingoItem` via [vich/uploader-bundle]. Affichée en background-image de la cellule quand présente. Stockage `public/uploads/items/`.

### 8.4 Emoji / sticker par case

Le design d'origine prévoit un emoji par case (avec picker). Ajouter `BingoItem::$emoji` (string nullable), migration, exposer dans la modale d'édition (Phase 5). L'afficher en grand dans le coin haut-gauche de la cellule.

### 8.5 Palettes (Tweaks panel)

Le design fournit 4 palettes pastel (lavande / ciel / sorbet / matcha). Panneau Tweaks discret en bas à droite : radio palette + densité grille (compact / regular / comfy). Persister en `localStorage` (ou sur `User::$preferences` une fois Phase 7 en place), appliquer en mettant à jour les CSS variables `--accent*` sur `<html>`.

**Note** : la sélection de palette de l'export image (Phase 6.3) doit pouvoir s'appuyer sur ce système de variables CSS — implémenter 7.5 avant ou en parallèle de l'export rend les deux features plus simples.

---

## Arborescence cible

```
bingo/
├── assets/
│   ├── app.js
│   ├── controllers/
│   │   ├── bingo_cell_controller.js        ✅
│   │   ├── bingo_board_controller.js       ✅
│   │   ├── cell_edit_modal_controller.js   🚧 Phase 5
│   │   ├── clipboard_controller.js         🚧 Phase 6
│   │   ├── bingo_export_controller.js      🚧 Phase 6.3
│   │   └── tweaks_controller.js            🚧 Phase 8.5
│   └── styles/
│       ├── app.css
│       └── components/bingo.css
├── src/
│   ├── Controller/
│   │   ├── BingoController.php             (home + show + share + new)
│   │   ├── BingoItemController.php         (check + edit)
│   │   ├── SecurityController.php          🚧 Phase 7
│   │   └── RegistrationController.php      🚧 Phase 7
│   ├── Entity/
│   │   ├── Bingo.php · BingoItem.php
│   │   └── User.php                        🚧 Phase 7
│   ├── Form/{BingoType, BingoItemType}.php 🚧 Phase 4/5
│   ├── Security/BingoVoter.php             🚧 Phase 7
│   ├── Repository/…
│   └── Service/BingoChecker.php
└── templates/
    ├── base.html.twig
    ├── home.html.twig                      🚧 Phase 4
    ├── security/login.html.twig            🚧 Phase 7
    └── bingo/
        ├── index.html.twig                 ✅ (grille active)
        ├── new.html.twig                   🚧 Phase 4
        ├── share.html.twig                 🚧 Phase 6
        ├── _export_panel.html.twig         🚧 Phase 6.3
        └── _item_edit.html.twig            🚧 Phase 5
```

---

## Ordre conseillé

2. **Multi-bingo** (Phase 4) — accueil + création, c'est ce qui fait que l'app cesse d'être mono-bingo
3. **Édition d'une case** (Phase 5) — débloquer le remplissage des bingos créés en 4
4. **Palettes / tweaks** (Phase 8.5) — pré-requis utile pour l'export image personnalisable
5. **Partage + export image** (Phase 6) — le slug est déjà en base, et l'export shippe un livrable visible immédiatement
6. **Multi-utilisateur** (Phase 7) — refactor de toutes les routes pour ownership ; à faire avant que tu partages l'URL à d'autres personnes IRL
7. **Polish restant** (Phase 8.2–8.4) selon l'envie
