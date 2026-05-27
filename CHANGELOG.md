# Changelog — Biopentra Loop Card

## [1.3.1] - 2026-05-27

### Fixed

- Plus/cart action now opens a full-card action menu across mobile, tablet, and desktop.
- Variation Strength selection now uses the same full-card overlay instead of a cramped image-only panel.
- Back and Close return to the normal product card state.
- Full-card overlay is absolutely positioned so it does not change card layout height, with internal scrolling limited to long variation lists only.

## [1.3.0] - 2026-05-27

### Added

- Compact floating cart action for Loop Grid product cards.
- Product links for the Loop Grid image and title when Elementor renders them without anchors.
- Source release coverage for the production category description and related research product modules.

### Changed

- Reworked quick-add overlay styling so options open in a compact upper-card panel over the image area.
- Tightened mobile card image, title, price, and overlay spacing for a shorter two-column layout.
- Updated card radius, image radius, border, and shadow styling for a softer premium presentation.

## [1.2.6] - 2026-05-20

**Standalone repository releases** — canonical GitHub home is [magpern/biopentra-loop-card](https://github.com/magpern/biopentra-loop-card) with `v*` tags.

### Added

- `includes/class-github-updater.php` — queries this repo's GitHub Releases (`/releases/latest`); installs `biopentra-loop-card-X.Y.Z.zip` only.
- `.github/workflows/ci.yml` and `.github/workflows/release.yml`.
- Disable on dev: `BIOPENTRA_LOOP_CARD_DISABLE_GITHUB_UPDATER` or filter `biopentra_loop_card_github_updater_enabled`.

### Notes

- Production ZIP excludes dev setup script `includes/setup-shop-page-cli.php`.
