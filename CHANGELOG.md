# Changelog

Tous les changements notables de ce bundle sont documentés dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).
Le schéma de version (X.Y.Z, canaux alpha/beta/main) est décrit dans
`.github/workflows/release.yml`.

## [Unreleased]

### Changed

- Aucun changement fonctionnel — bump de version pour aligner ce bundle sur la ligne 1.1.0 commune à tous les bundles officiels.
- Le CSS (`.timeclock-*`) et les deux JS (`timeclock.js`, `timeclock-admin.js`) vivaient dans Kintai Core, pas dans ce dépôt. Ils vivent maintenant dans `public/css/timeclock.css`/`public/js/timeclock.js`/`public/js/timeclock-admin.js`, fournis par le bundle lui-même via `Bundle::loadAssetsFrom()`/`bundle_asset()`. **Nécessite** `kintai_core.min: "0.2.0"`.

## [1.0.0] - 2026-09-19

### Added

- Extraction initiale depuis Kintai (`src/Bundles/Timeclock`).
