# Changelog

Tous les changements notables de ce bundle sont documentés dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).
Le schéma de version (X.Y.Z, canaux alpha/beta/main) est décrit dans
`.github/workflows/release.yml`.

## [Unreleased]

### Changed

- Contrat de bundle versionné : le bundle n'utilise plus que les noms du contrat `kintai\Contract\…` du Core (requête, réponse, routes, middlewares, exceptions, droits, données du Core, services, vues et composants) et déclare `"kintai_contract": "^1.0"` dans `bundle.json` (`kintai_core.min` relevé à `0.3.6`, première version du Core qui fournit le contrat). Nouveau test `ContractUsageTest` : toute utilisation du Core hors contrat échoue, hormis 1 classe(s) propre(s) au bundle qui vivent encore dans le Core (extraction prévue).

### Added

- Contrat de bundle 1.11 : le bundle déclare lui-même ce qu'il apporte au Core (`navigation()`, `permissions()`, `storeFeatures()`, `attendanceProviders()`) au lieu que le Core le connaisse à l'avance ; déclarations identiques à ce que le Core affichait pour ce bundle, rien ne change à l'écran. `bundle.json` demande désormais `"kintai_contract": "^1.11"` (Kintai Core avec les points d'extension et fournisseurs du contrat). Nouveau test `ContractDeclarationsTest` : chaque déclaration est comparée au repli historique du Core.
- Socle de tests PHPUnit (composer.json, phpunit.xml) et CI qui l'exécute contre le vrai Core : les tests que le Core faisait tourner sur une copie figée du bundle (fixtures 1.0.0) vivent désormais ici et testent le code réel.

### Fixed

- Pointage employé multi-magasins : le magasin était toujours le premier de l'employé, et le `store_id` envoyé par le navigateur était ignoré. Un sélecteur de magasin apparaît désormais quand l'employé en a plusieurs ; le magasin choisi doit être l'un des siens. Un employé sans magasin ne peut plus pointer (la ligne était créée avec `store_id = 0`).

### Fixed

- Sécurité — `POST /api/v1/timeclocks/clock-in` acceptait n'importe quel `store_id` du corps, même un magasin dont l'employé n'est pas membre, et `PUT /api/v1/timeclocks/{id}` laissait modifier `store_id`/`user_id` (déplacer le pointage vers un magasin tiers contournait le contrôle de `requireTimeclock()`). Le magasin du clock-in doit être celui d'un magasin de l'employé, et `user_id`/`store_id`/`created_at` ne changent plus en modification.

### Changed

- Garde-fou contre les handlers inline, en prévision de la Content-Security-Policy stricte de Kintai Core 0.3.0 (`script-src 'self' 'nonce-…'`, sans `'unsafe-inline'`) : `tests.yml` échoue désormais si un attribut `onclick=`/`onchange=`/`onsubmit=`/`oninput=`, un lien `javascript:` ou un `<script>` sans nonce apparaît dans `Views/` ou `src/` — le navigateur les bloquerait en silence, sans aucune erreur côté serveur. **Aucun changement fonctionnel** : les vues de ce bundle n'utilisent déjà aucun handler inline ni `<script>` inline exécutable. La règle est documentée dans `CONTRIBUTING.md` et `CLAUDE.md`.

### Changed

- Aucun changement fonctionnel — bump de version pour aligner ce bundle sur la ligne 1.1.0 commune à tous les bundles officiels.
- Le CSS (`.timeclock-*`) et les deux JS (`timeclock.js`, `timeclock-admin.js`) vivaient dans Kintai Core, pas dans ce dépôt. Ils vivent maintenant dans `public/css/timeclock.css`/`public/js/timeclock.js`/`public/js/timeclock-admin.js`, fournis par le bundle lui-même via `Bundle::loadAssetsFrom()`/`bundle_asset()`. **Nécessite** `kintai_core.min: "0.2.0"`.

## [1.0.0] - 2026-09-19

### Added

- Extraction initiale depuis Kintai (`src/Bundles/Timeclock`).
