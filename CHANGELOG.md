# Changelog

Tous les changements notables de ce bundle sont documentés dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).
Le schéma de version (X.Y.Z, canaux alpha/beta/main) est décrit dans
`.github/workflows/release.yml`.

## [Unreleased]

### Changed

- Garde-fou contre les handlers inline, en prévision de la Content-Security-Policy stricte de Kintai Core 0.3.0 (`script-src 'self' 'nonce-…'`, sans `'unsafe-inline'`) : `tests.yml` échoue désormais si un attribut `onclick=`/`onchange=`/`onsubmit=`/`oninput=`, un lien `javascript:` ou un `<script>` sans nonce apparaît dans `Views/` ou `src/` — le navigateur les bloquerait en silence, sans aucune erreur côté serveur. **Aucun changement fonctionnel** : les vues de ce bundle n'utilisent déjà aucun handler inline ni `<script>` inline exécutable. La règle est documentée dans `CONTRIBUTING.md` et `CLAUDE.md`.

### Changed

- Aucun changement fonctionnel — bump de version pour aligner ce bundle sur la ligne 1.1.0 commune à tous les bundles officiels.
- Le CSS (`.timeclock-*`) et les deux JS (`timeclock.js`, `timeclock-admin.js`) vivaient dans Kintai Core, pas dans ce dépôt. Ils vivent maintenant dans `public/css/timeclock.css`/`public/js/timeclock.js`/`public/js/timeclock-admin.js`, fournis par le bundle lui-même via `Bundle::loadAssetsFrom()`/`bundle_asset()`. **Nécessite** `kintai_core.min: "0.2.0"`.

## [1.0.0] - 2026-09-19

### Added

- Extraction initiale depuis Kintai (`src/Bundles/Timeclock`).
