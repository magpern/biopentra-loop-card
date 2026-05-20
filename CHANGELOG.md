# Changelog — Biopentra Loop Card

## [1.2.6] - 2026-05-20

**Standalone repository releases** — canonical GitHub home is [magpern/biopentra-loop-card](https://github.com/magpern/biopentra-loop-card) with `v*` tags.

### Added

- `includes/class-github-updater.php` — queries this repo's GitHub Releases (`/releases/latest`); installs `biopentra-loop-card-X.Y.Z.zip` only.
- `.github/workflows/ci.yml` and `.github/workflows/release.yml`.
- Disable on dev: `BIOPENTRA_LOOP_CARD_DISABLE_GITHUB_UPDATER` or filter `biopentra_loop_card_github_updater_enabled`.

### Notes

- Production ZIP excludes dev setup script `includes/setup-shop-page-cli.php`.
