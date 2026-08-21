# Release notes — biopentra-loop-card v1.6.2

**M4 — Premium Product Cards** — PO-approved / frozen on `dev.biopentra.eu`.

## Summary

Refines the canonical soft/rounded/elevated product card for Premium Ecommerce hierarchy without flattening to M3 low-radius chip geometry.

## Changes

- Stronger image inset + paper image ground; slightly taller image budget
- Teal title hierarchy (`#0a303e`)
- Cyan price emphasis (`#0088b0`)
- Quieter circular quick-add with Premium cyan accents (touch ≥36px)
- Soft dual elevation shadow and ~12–17px radius **preserved**

## Upgrade notes

- Pair with `biopentra-storefront` **0.9.17** / tag `storefront-v0.9.17` for homepage section chrome.
- After deploy: `wp elementor flush-css && wp cache flush`.

## Rollback

Install v1.6.1 release ZIP (+ storefront **0.9.16** if rolling back M4 section chrome).
