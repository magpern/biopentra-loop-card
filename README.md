# Biopentra Loop Card

WordPress plugin for Elementor **Loop Grid** product cards (WooCommerce).

**Canonical source and releases:** [magpern/biopentra-loop-card](https://github.com/magpern/biopentra-loop-card)

## Features

- Image, title, price on the card
- Hover overlay: variation / strength picker, AJAX add to cart
- Out-of-stock and partial-stock UI
- Shop loop filters and live search (see plugin code)

## Requirements

- WordPress 6.0+
- WooCommerce
- Elementor Pro (Loop Grid)

## Development

```bash
bash scripts/build-zip.sh
bash scripts/release-audit.sh
```

Tag `v{version}` (e.g. `v1.3.2`) to publish a GitHub Release with `biopentra-loop-card-{version}.zip`.

## Monorepo mirror

A copy may exist under `biopentra-custom-plugins/plugins/biopentra-loop-card/` for local dev rsync only. **Do not** release from the monorepo; use this repository.
