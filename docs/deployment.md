# Déploiement

Le projet est déployé sur un hébergeur mutualisé Apache (`bingo.antoninpamart.fr`). Le déploiement est piloté par GitHub Actions qui se connecte en SSH et exécute `deploy.sh` côté serveur.

## Vue d'ensemble

```
git push origin v1.2.3
      │
      ▼
GitHub Actions (.github/workflows/deploy.yml)
      │  ssh -i id_bingo
      ▼
Serveur : cd $APP_DIR && ./deploy.sh
      │
      ├── git fetch --tags
      ├── git checkout <dernier tag>
      ├── composer install --no-dev --optimize-autoloader
      ├── doctrine:migrations:migrate --env=prod
      ├── tailwind:build --minify
      ├── asset-map:compile
      └── cache:clear --env=prod
```

## Déclenchement

Le workflow `.github/workflows/deploy.yml` se déclenche dans deux cas :

- **Push d'un tag `v*`** → déploie ce tag.
- **`workflow_dispatch`** (lancement manuel) → redéploie simplement le dernier tag présent sur `origin`. Utile pour rollforward après modification de config serveur.

Une `concurrency: group: deploy` empêche deux déploiements simultanés (sinon `composer` et les migrations entreraient en conflit).

## Cycle de release

1. Mettre à jour `CHANGELOG.md`. Le projet suit [Keep a Changelog](https://keepachangelog.com/) et [SemVer](https://semver.org/).
2. Créer un commit `release: X.Y.Z` (cf. `git log` pour le style).
3. Taguer : `git tag vX.Y.Z && git push origin vX.Y.Z`.
4. Le workflow Actions s'exécute, le tag est checkout sur le serveur, le script termine par `echo "Deploy terminé."`.

## Script `deploy.sh`

```bash
#!/bin/bash
set -e
source ~/.bashrc
git fetch --tags
git checkout $(git tag --sort=-version:refname | head -1)
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console tailwind:build --minify
php bin/console asset-map:compile
php bin/console cache:clear --env=prod
```

Le script est volontairement minimal — pas de zero-downtime, pas de symlink. Une coupure très courte est acceptée pendant le `cache:clear`.

## Secrets / variables GitHub

Définis au niveau du dépôt (`Settings → Secrets and variables → Actions`) :

| Nom               | Type     | Usage                                         |
|-------------------|----------|-----------------------------------------------|
| `SSH_PRIVATE_KEY` | secret   | Clé privée injectée dans `~/.ssh/id_bingo`    |
| `SSH_HOST`        | variable | Hostname du serveur                           |
| `SSH_USER`        | variable | Utilisateur SSH                               |
| `SSH_PORT`        | variable | Port SSH                                      |
| `APP_DIR`         | variable | Chemin absolu du projet sur le serveur        |

La clé privée est supprimée systématiquement en fin de job (`if: always()`).

## Contraintes hébergeur (Apache mutualisé)

### Alias `/icons/` global

L'hébergeur déclare un `Alias /icons/ /usr/share/apache2/icons/` au niveau du serveur pour les icônes d'auto-index Apache. Cet alias **masque** tout dossier `public/icons/` du projet — les fichiers existeraient mais Apache servirait les siennes à la place.

**Conséquence** : les icônes PWA et tout asset statique « par convention » dans un dossier sensible sont placés dans `public/pwa/` (cf. `public/manifest.json` et `public/sw.js`). Ne pas créer `public/icons/`.

D'autres alias système peuvent exister (`/cgi-bin/`, `/error/`, etc.). En cas de 404 inexpliquée sur un nouvel asset, vérifier d'abord qu'aucun alias ne capte le chemin.

### `.htaccess`

`public/.htaccess` est fourni par `symfony/apache-pack`. Il gère la réécriture vers `index.php`, les headers de cache pour les assets hashés, et les protections standards.

### Permissions

Les dossiers `var/`, `public/uploads/`, et `public/assets/` doivent être writables par le user PHP de l'hébergeur. Le `chmod 777` à la racine du projet (`var/` est en `drwxrwxrwx`) reflète cette contrainte.

## Vérifications post-déploiement

- Charger la home `/` → la landing doit s'afficher.
- Se connecter, ouvrir un bingo, toggle une case → la requête `POST /bingo/{id}/check` doit renvoyer 200 + JSON.
- Vérifier le manifeste PWA dans DevTools → Application → Manifest : les icônes doivent charger depuis `/pwa/icon-*.png`.
- Vérifier que le SW a bien activé la nouvelle version (`Application → Service Workers`).

## Rollback

Pas de rollback automatique. Pour revenir à une version antérieure :

```bash
# Sur le serveur, en SSH
cd $APP_DIR
git checkout v1.1.0
composer install --no-dev --optimize-autoloader
# Reverter les migrations si nécessaire :
php bin/console doctrine:migrations:migrate prev --env=prod
php bin/console tailwind:build --minify
php bin/console asset-map:compile
php bin/console cache:clear --env=prod
```

Attention aux migrations destructrices : `migrate prev` n'est sûr que si la migration concernée définit un `down()` correct.
