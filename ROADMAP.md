# Roadmap — Bingo Annuel

## Stack actuelle

- **Back** : Symfony 8 · Doctrine ORM · PHP 8.4
- **Front** : AssetMapper · Stimulus (auto-discovery) · pas de Turbo
- **Style** : Tailwind v4 (bundle Symfonycasts) + CSS custom · polices Google (Instrument Serif · Plus Jakarta Sans · Caveat)
- **Data flow** : POST JSON → JS Stimulus met à jour le DOM (sans Turbo Frames). Pas de full reload.

---

## ✅ Phase 1 — Foundation

## ✅ Phase 2 — Interactivité
           
## ✅ Phase 3 — Design pastel

## ✅ Phase 4 — Multi-bingo (accueil + création)

## ✅ Phase 5 — Édition d'une case

## ✅ Phase 6 — Partage

## ✅ Phase 7 — Multi-utilisateur

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
