# Biopentra Loop Card 1.5.0

## Summary

Milestone B support: shop category descriptions below the product grid and live product search on search results pages.

## Changes

- Shop category SEO descriptions inject **after** the loop grid (`shop-category-description.php`).
- Live product search assets enqueue on `is_search()` in addition to shop/home contexts.
- Version constant `BIOPENTRA_LOOP_CARD_VER` aligned to **1.5.0**.

## Testing

1. Upload and activate `biopentra-loop-card-1.5.0.zip` on the test site.
2. Verify `/shop/` category descriptions appear below the product grid.
3. Run a product search (`/?s=peptide&post_type=product`) and confirm live search behaviour.
4. Confirm homepage and shop loop cards still enhance with quick-add overlay.
5. Confirm no horizontal overflow at mobile widths.

## Rollback

Reinstall the previous approved `biopentra-loop-card` release zip, then flush object cache and regenerate Elementor CSS.
