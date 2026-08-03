# Release notes — biopentra-loop-card v1.6.0

**Milestone C — Canonical product card renderer + adapters**

## Summary

Introduces a single canonical product-card renderer (Elementor loop template **3608**) with thin adapters for WooCommerce loops and programmatic PHP grids. WC archives, product search, related/upsells, and sidebar related research now share the same card implementation as Elementor shop/home grids.

## Architecture

See `docs/storefront-redesign/design-system/canonical-product-card-architecture.md` in biopentra-custom-plugins.

| Component | Path |
|---|---|
| Renderer | `includes/class-product-card-renderer.php` |
| WC adapter | `includes/adapters/wc-content-product-adapter.php` |
| Programmatic adapter | `includes/adapters/programmatic-adapter.php` |

## Upgrade notes

- Requires Elementor Pro Loop Builder and loop template post ID **3608**.
- WC integration uses `wc_get_template_part` (not `woocommerce_locate_template`).
- After deploy: `wp elementor flush-css && wp cache flush`.

## Rollback

Install v1.5.0 release ZIP. WC archives revert to theme native cards automatically.
