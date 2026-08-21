# Biopentra Loop Card 1.6.3 — release notes (draft)

**M4 desktop product-card corrective** (post-freeze). Awaiting Product Owner visual review on DEV before freeze tag.

## Fixes

- Desktop (≥1025) image framing: taller image budget + top-biased `object-position`; keep `object-fit: cover`
- Quick-add glyph: geometric centered `+` (no font-metric drift)
- Variable quick-add panel: grow overlay so Close / Strength / options / Clear / Add are fully visible

## Unchanged

- Soft/rounded/elevated M4 card design
- Initial menu (Close / View product / Quick add)
- Mobile/tablet card geometry
- WooCommerce add-to-cart semantics

## Install

Deploy `biopentra-loop-card` **1.6.3** after PO approval and tagging as `v1.6.3`.

Rollback: **1.6.2** / `v1.6.2`.
