# Biopentra Loop Card 1.3.0

## Summary

- Compact premium card styling for Elementor Loop Grid product cards across mobile, tablet, and desktop.
- Adds a small floating cart action and moves quick-add options into a compact upper-card overlay over the image area.
- Keeps image/title product links, variation selection, AJAX add-to-cart, mini-cart refresh events, out-of-stock handling, shop filters, category descriptions, and related research product modules.

## Testing

1. Upload and activate `biopentra-loop-card-1.3.0.zip` on the test site.
2. Verify `/shop/` and homepage product sections at mobile, tablet, and desktop widths.
3. Confirm image/title links navigate to the product page.
4. Confirm cart action opens variation options for variable products and does not add without a selection.
5. Confirm simple products AJAX add to cart and refresh the mini-cart.
6. Confirm out-of-stock products remain clear and non-purchasable.

## Rollback

Reinstall the previous approved `biopentra-loop-card` release zip, then flush object cache and regenerate Elementor CSS.
