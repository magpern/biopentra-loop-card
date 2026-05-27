# Biopentra Loop Card 1.3.2

## Summary

- Tightens product card spacing so mobile, tablet, and desktop cards feel shorter, denser, and more premium.
- Increases product image height and removes inherited Elementor card gap/padding that created dead vertical space.
- Preserves the `1.3.1` full-card quick-add overlay state model without letting the overlay affect layout height.

## Testing

1. Upload and activate `biopentra-loop-card-1.3.2.zip` on the test site.
2. Verify `/shop/` and homepage product sections at mobile 390px, tablet, and desktop widths.
3. Confirm card height is visually reduced, image area is visually larger, and title/price spacing is tighter.
4. Confirm equal-height product grid alignment remains consistent.
5. Confirm plus opens the full-card action menu and Quick add opens Strength selection.
6. Confirm simple and variable AJAX add-to-cart still update the mini-cart.
7. Confirm no horizontal overflow.
8. Confirm overlay remains absolutely positioned and does not reserve layout height.

## Rollback

Reinstall the previous approved `biopentra-loop-card` release zip, then flush object cache and regenerate Elementor CSS.
