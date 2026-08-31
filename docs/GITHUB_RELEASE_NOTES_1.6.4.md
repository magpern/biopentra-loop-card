# Biopentra Loop Card 1.6.4 — release notes

**M9 shop search companion** — **PO-approved / frozen on DEV** with storefront **`storefront-v0.9.30`**.

## Fixes

- When forcing the Elementor shop page as singular, clear main-query `is_search` / `s` so `/shop/?s=` keeps Elementor content and filters the loop grid via `$_GET['s']` (previously WP search template blanked the shop page).

## Companion

- Pair with `biopentra-storefront` **0.9.28–0.9.30** M9 Shop Discovery.

## Install

Deploy `biopentra-loop-card` **1.6.4** / tag **`v1.6.4`**.

Rollback: **1.6.3** / `v1.6.3`.
