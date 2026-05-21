# Roadmap — Bingo Annuel (Symfony UX + Turbo)

## Stack
---

## Phase 1 — Foundation

**Objectif : afficher une grille statique avec des données en base.**

### 1.2 Entités

#### `src/Entity/Bingo.php`
```php
#[ORM\Entity]
class Bingo {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\Column]
    private int $year;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 8, unique: true)]
    private string $slug; // ex: "a3f9k2b1" — pour le partage

    #[ORM\OneToMany(targetEntity: BingoItem::class, mappedBy: 'bingo', cascade: ['persist', 'remove'])]
    private Collection $items;
}
```

#### `src/Entity/BingoItem.php`
```php
#[ORM\Entity]
class BingoItem {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private Bingo $bingo;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column]
    private int $position; // 0–15 (grille 4×4)

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note;
}
```

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Fichiers à créer/vérifier :
- `migrations/VersionXXXX.php` (auto-généré)
- `src/Entity/Bingo.php`
- `src/Entity/BingoItem.php`

### 1.3 Fixtures (données de test)

Installer : `composer require --dev doctrine/doctrine-fixtures-bundle`

#### `src/DataFixtures/BingoFixtures.php`
- Crée un `Bingo` année 2026
- Crée 16 `BingoItem` avec les cases de ton vrai bingo
- Quelques cases cochées pour tester l'affichage

```bash
php bin/console doctrine:fixtures:load
```

### 1.4 Controller + route principale

#### `src/Controller/BingoController.php`
```php
#[Route('/bingo/{year}', name: 'bingo_show')]
public function show(int $year, BingoRepository $repo): Response
{
    $bingo = $repo->findOneBy(['year' => $year]);
    // ...
    return $this->render('bingo/show.html.twig', ['bingo' => $bingo]);
}
```

### 1.5 Template de la grille

#### `templates/bingo/show.html.twig`
- Layout `base.html.twig`
- Grille CSS Grid 4×4
- Chaque case = `<div class="bingo-item {{ item.completedAt ? 'completed' : '' }}">`
- Texte centré, croix rouge si cochée

#### `templates/base.html.twig`
- `<head>` avec asset Twig, lien CSS, import JS Stimulus/Turbo
- `{% block body %}`

#### `assets/styles/app.css`
```css
.bingo-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}
.bingo-item { /* style de case */ }
.bingo-item.completed { /* croix rouge, opacité */ }
```

**Livrable phase 1 : `/bingo/2026` affiche ta grille.**

---

## Phase 2 — Interactivité (Turbo)

**Objectif : cocher/décocher une case sans rechargement. Détecter le bingo.**

### 2.1 Route de toggle

#### `src/Controller/BingoController.php` — ajout méthode
```php
#[Route('/bingo/item/{id}/toggle', name: 'bingo_item_toggle', methods: ['POST'])]
public function toggle(BingoItem $item, EntityManagerInterface $em): Response
{
    if ($item->getCompletedAt()) {
        $item->setCompletedAt(null);
    } else {
        $item->setCompletedAt(new \DateTimeImmutable());
    }
    $em->flush();

    return $this->render('bingo/_item.html.twig', [
        'item' => $item,
        'bingo' => $item->getBingo(),
    ]);
}
```

### 2.2 Turbo Frame sur chaque case

#### `templates/bingo/_item.html.twig` (partial)
```twig
<turbo-frame id="item-{{ item.id }}">
    <form action="{{ path('bingo_item_toggle', {id: item.id}) }}" method="post">
        <button type="submit" class="bingo-item {{ item.completedAt ? 'completed' : '' }}">
            {{ item.label }}
        </button>
    </form>
</turbo-frame>
```

Dans `show.html.twig`, chaque case devient :
```twig
{% for item in bingo.items|sort((a,b) => a.position <=> b.position) %}
    {{ include('bingo/_item.html.twig') }}
{% endfor %}
```

Turbo intercepte le POST et remplace uniquement le `<turbo-frame>` ciblé.

### 2.3 Détection bingo côté serveur

#### `src/Service/BingoChecker.php`
```php
class BingoChecker {
    // Retourne les combinaisons gagnantes (lignes, colonnes, diagonales)
    // complètes parmi les items cochés
    public function getCompletedLines(Bingo $bingo): array { ... }
    public function hasBingo(Bingo $bingo): bool { ... }
}
```

Les 10 combinaisons à vérifier (indices 0–15) :
```
Lignes :     [0,1,2,3], [4,5,6,7], [8,9,10,11], [12,13,14,15]
Colonnes :   [0,4,8,12], [1,5,9,13], [2,6,10,14], [3,7,11,15]
Diagonales : [0,5,10,15], [3,6,9,12]
```

### 2.4 Turbo Stream — animation bingo

Quand `toggle` détecte un bingo complété, retourner un Turbo Stream en plus du frame :

#### `templates/bingo/_bingo_flash.html.twig`
```twig
<turbo-stream action="append" target="bingo-flashes">
    <template>
        <div class="bingo-flash">BINGO !</div>
    </template>
</turbo-stream>
```

Dans le controller, vérifier après flush si une ligne vient d'être complétée et retourner le stream.

**Livrable phase 2 : cases cochables au clic, animation BINGO détectée.**

---

## Phase 3 — Multi-années

**Objectif : gérer plusieurs bingos, page d'accueil avec historique.**

### 3.1 Page d'accueil

#### `src/Controller/BingoController.php` — méthode index
```php
#[Route('/', name: 'home')]
public function index(BingoRepository $repo): Response
{
    $bingos = $repo->findBy([], ['year' => 'DESC']);
    return $this->render('bingo/index.html.twig', ['bingos' => $bingos]);
}
```

#### `templates/bingo/index.html.twig`
- Liste des bingos avec : année, titre, `X/16 cases`, barre de progression
- Lien vers `/bingo/{year}`
- Bouton "Nouveau bingo"

### 3.2 Stats par bingo

#### `src/Repository/BingoRepository.php`
```php
public function getStats(Bingo $bingo): array
{
    // Retourne : total, completed, percent, completedLines
}
```

Affiché dans `index.html.twig` et en en-tête de `show.html.twig`.

### 3.3 Création d'un nouveau bingo

#### `src/Form/BingoType.php`
- Champ `year`, `title`
- Collection de 16 `BingoItemType` (juste le `label`)

#### `src/Controller/BingoController.php` — méthode new
```php
#[Route('/bingo/new', name: 'bingo_new')]
public function new(Request $request, EntityManagerInterface $em): Response { ... }
```

#### `templates/bingo/new.html.twig`
- Formulaire avec 16 champs texte disposés en grille 4×4
- Stimulus controller pour drag-and-drop des cases (optionnel)

**Livrable phase 3 : tu peux créer ton bingo 2027 depuis l'interface.**

---

## Phase 4 — Partage

**Objectif : lien unique partageable, vue lecture seule.**

### 4.1 Génération du slug

Dans `src/Entity/Bingo.php`, générer le slug à la création :
```php
#[ORM\PrePersist]
public function generateSlug(): void
{
    $this->slug = substr(bin2hex(random_bytes(4)), 0, 8);
}
```

### 4.2 Route publique

#### `src/Controller/BingoController.php` — méthode share
```php
#[Route('/b/{slug}', name: 'bingo_share')]
public function share(string $slug, BingoRepository $repo): Response
{
    $bingo = $repo->findOneBy(['slug' => $slug]);
    return $this->render('bingo/share.html.twig', ['bingo' => $bingo]);
}
```

#### `templates/bingo/share.html.twig`
- Même grille que `show.html.twig` mais sans formulaires (lecture seule)
- Bandeau "Bingo de [prénom] — 2026"
- Stats en haut (X/16, % complétion)

### 4.3 Bouton de partage dans show.html.twig

```twig
<a href="{{ path('bingo_share', {slug: bingo.slug}) }}" class="btn-share">
    Partager mon bingo
</a>
```

Ou via Stimulus : copier l'URL dans le presse-papier au clic.

#### `assets/controllers/clipboard_controller.js`
```js
import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    copy() {
        navigator.clipboard.writeText(this.element.dataset.url);
        // feedback visuel
    }
}
```

### 4.4 Meta OG

Dans `templates/bingo/share.html.twig` :
```twig
{% block meta %}
    <meta property="og:title" content="Bingo {{ bingo.year }} — {{ bingo.completedCount }}/16 cases" />
    <meta property="og:description" content="Suis ma progression !" />
{% endblock %}
```

**Livrable phase 4 : un lien `/b/a3f9k2b1` partageable sur les réseaux.**

---

## Phase 5 — Polish

**Objectif : noter les cases cochées, PWA, photo.**

### 5.1 Note sur une case cochée

Au clic sur une case déjà cochée → modale (Turbo Frame dans un `<dialog>`) pour ajouter/éditer la note.

#### `src/Controller/BingoController.php` — méthode edit item
```php
#[Route('/bingo/item/{id}/edit', name: 'bingo_item_edit')]
public function editItem(BingoItem $item, Request $request, EntityManagerInterface $em): Response { ... }
```

#### `templates/bingo/_item_modal.html.twig`
```twig
<turbo-frame id="modal">
    <dialog open>
        <form method="post" ...>
            <textarea name="note">{{ item.note }}</textarea>
            <button type="submit">Sauvegarder</button>
        </form>
    </dialog>
</turbo-frame>
```

### 5.2 Date de complétion affichée

Dans `_item.html.twig` :
```twig
{% if item.completedAt %}
    <span class="completed-date">{{ item.completedAt|date('d/m/Y') }}</span>
{% endif %}
```

### 5.3 PWA

#### `public/manifest.json`
```json
{
    "name": "Mon Bingo Annuel",
    "short_name": "Bingo",
    "start_url": "/",
    "display": "standalone",
    "background_color": "#111",
    "theme_color": "#111",
    "icons": [{ "src": "/icon-192.png", "sizes": "192x192" }]
}
```

Dans `base.html.twig` :
```twig
<link rel="manifest" href="/manifest.json">
```

### 5.4 Photo sur une case (optionnel)

Installer : `composer require vich/uploader-bundle`

Ajouter `imageFile` (VichUploader) et `imageName` (string) à `BingoItem`.
Afficher la photo en fond de la case dans `_item.html.twig`.

---

## Arborescence finale du projet

```
bingo/
├── assets/
│   ├── app.js
│   ├── controllers.json
│   ├── controllers/
│   │   └── clipboard_controller.js
│   └── styles/
│       └── app.css
├── migrations/
│   └── VersionXXXX.php
├── public/
│   ├── manifest.json
│   └── icon-192.png
├── src/
│   ├── Controller/
│   │   └── BingoController.php
│   ├── DataFixtures/
│   │   └── BingoFixtures.php
│   ├── Entity/
│   │   ├── Bingo.php
│   │   └── BingoItem.php
│   ├── Form/
│   │   ├── BingoType.php
│   │   └── BingoItemType.php
│   ├── Repository/
│   │   ├── BingoRepository.php
│   │   └── BingoItemRepository.php
│   └── Service/
│       └── BingoChecker.php
└── templates/
    ├── base.html.twig
    └── bingo/
        ├── index.html.twig
        ├── show.html.twig
        ├── new.html.twig
        ├── share.html.twig
        ├── _item.html.twig
        ├── _item_modal.html.twig
        └── _bingo_flash.html.twig
```

---

## Ordre d'attaque recommandé

1. Entités + migration + fixtures
2. `show.html.twig` statique avec CSS Grid
3. Toggle via Turbo Frame
4. `BingoChecker` + flash Turbo Stream
5. Page index + stats
6. Formulaire création
7. Slug + route partage
8. Polish (notes, date, PWA)
