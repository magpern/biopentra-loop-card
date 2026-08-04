# Changelog — Biopentra Loop Card

## [1.6.1] - 2026-08-04

### Added

- Card image width/height/decoding attributes and first-card LCP promotion (`card-image-attributes.php`, `loop-card.js`) for Milestone D1 image budgets.

## [1.6.0] - 2026-08-03

### Added

- Canonical product-card renderer service (`Biopentra_Loop_Card_Product_Card_Renderer`) with render contexts, Elementor 3608 rendering, fallback, and renderer versioning.
- WooCommerce `content-product` adapter via `wc_get_template_part` for archives, search, related, upsells, and cross-sells.
- Programmatic adapter for ordered product ID grids (sidebar related research, shortcodes).
- Architecture reference: see biopentra-custom-plugins `canonical-product-card-architecture.md`.

### Changed

- Sidebar related research products use canonical renderer (compact layout) instead of custom card HTML.
- WC loop integration CSS (`canonical-wc-loop.css`) for grid layout only — no forked card styling.

## [1.5.0] - 2026-08-02

### Changed

- Milestone B: shop category copy below grid; live search on search results.

## [1.3.3] - 2026-05-28

### Changed

- Quick-add overlay now slides upward from the bottom of the card while staying absolutely positioned.
- Reduced the remaining lower card whitespace by trimming card container and price bottom spacing.

## [1.3.2] - 2026-05-27

### Changed

- Removed inherited Elementor card gap and excess container padding that made product cards feel stretched.
- Increased product image height across desktop, tablet, and mobile so cards read as image-led.
- Tightened image, title, and price spacing while preserving readable typography and touch targets.
- Kept the quick-add overlay absolutely positioned and out of layout flow.

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
