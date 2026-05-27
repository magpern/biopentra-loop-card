# Biopentra Loop Card 1.3.3

## Summary

- Adds a bottom-to-top slide animation for the full-card quick-add overlay.
- Slightly reduces remaining product card bottom whitespace without shrinking the image area.
- Keeps the overlay absolutely positioned so it does not reserve or change layout height.

## Testing

1. Upload and activate `biopentra-loop-card-1.3.3.zip` on the test site.
2. Verify `/shop/` and homepage product sections at mobile, tablet, and desktop widths.
3. Confirm plus opens the full-card overlay with a bottom-up slide.
4. Confirm Quick add opens Strength selection and Back/Close exits to the normal card.
5. Confirm card height is slightly reduced and there is less bottom whitespace.
6. Confirm no horizontal overflow.
7. Confirm AJAX add-to-cart still updates the mini-cart.

## Rollback

Reinstall the previous approved `biopentra-loop-card` release zip, then flush object cache and regenerate Elementor CSS.
