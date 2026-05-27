# Biopentra Loop Card 1.3.1

## Summary

- Fixes the product card quick-add overlay so the plus/cart action opens a full-card menu on mobile, tablet, and desktop.
- Keeps the action flow clear: plus opens menu, Quick add opens Strength selection, Back/Close exits to the normal card.
- Removes the image-only overlay constraint and keeps the overlay absolutely positioned so card layout height stays stable.

## Testing

1. Upload and activate `biopentra-loop-card-1.3.1.zip` on the test site.
2. Verify `/shop/` and homepage product sections at mobile 390px, tablet, and desktop widths.
3. Confirm image/title links navigate to product pages when the overlay is closed.
4. Confirm plus opens the full-card action menu.
5. Confirm Quick add opens full-card Strength selection for variable products.
6. Confirm Back and Close return to the normal product card state.
7. Confirm selecting an available strength enables Add to cart and refreshes the mini-cart.
8. Confirm out-of-stock strengths remain disabled.
9. Confirm the overlay does not change card layout height or create internal card scrollbars unless variation options overflow.

## Rollback

Reinstall the previous approved `biopentra-loop-card` release zip, then flush object cache and regenerate Elementor CSS.
