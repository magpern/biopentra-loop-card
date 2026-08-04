# Release notes — biopentra-loop-card v1.6.1

**Milestone D1 — Card image attributes + LCP hints**

## Summary

Adds explicit card image sizing / decoding attributes and LCP promotion for the first visible product card image so commercial grids meet Milestone D image-budget acceptance.

## Changes

- `includes/card-image-attributes.php` — width/height/decoding/fetchpriority helpers for loop card media.
- `assets/loop-card.js` — promote the first in-viewport card image as LCP candidate where appropriate.

## Upgrade notes

- Pair with `biopentra-storefront` **0.8.0** (SDS tokens / hero caps) and `biopentra-blocksy-child` **1.1.0** (PDP gallery caps).
- After deploy: `wp elementor flush-css && wp cache flush`.

## Rollback

Install v1.6.0 release ZIP.
