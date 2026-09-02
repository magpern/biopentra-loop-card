=== Biopentra Loop Card ===
Contributors: magpern
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.7.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Elementor Loop Grid product cards with hover overlay, variation picker, AJAX add to cart, and shop filters.

== Description ==

WordPress plugin for Elementor Loop Grid product cards on WooCommerce shops.

== Installation ==

1. Upload `biopentra-loop-card-1.7.0.zip` via Plugins → Add New → Upload.
2. Activate the plugin.

Requires WooCommerce and Elementor Pro (Loop Grid).

== Changelog ==

= 1.7.1 =
* Update-server constant renamed to PRIVATE_UPDATE_SERVER (brand-neutral, shared across all Biopentra plugins). Define it in wp-config.php.

= 1.7.0 =
* Automatic updates now come from the private update server via the bundled Plugin Update Checker library; the previous direct GitHub-release updater has been removed.

= 1.6.4 =
* M9 shop search: keep the Elementor shop page as singular while filtering the loop grid via the search query.

= 1.6.0 – 1.6.3 =
* M4 premium card refinement and desktop image-framing correctives.

= 1.5.0 =
* Card navigation and stock-banner refinements.

= 1.3.3 =
* Animate the full-card quick-add overlay upward from the bottom of the card.
* Slightly reduce remaining card bottom whitespace while preserving image dominance.

= 1.3.2 =
* Tighten Elementor Loop Grid card spacing by removing inherited template gap and dead vertical padding.
* Increase image dominance across breakpoints while keeping equal-height grid behavior.
* Keep the quick-add overlay absolute so it does not reserve layout height.

= 1.3.1 =
* Fix quick-add overlay state model so plus opens a full-card action menu across all breakpoints.
* Move Strength selection into the same full-card overlay and make Back/Close exit to the normal card.
* Avoid image-only overlay sizing and avoid internal scrollbars unless variation options require it.

= 1.3.0 =
* Compact premium product-card styling for Elementor Loop Grid cards.
* Add image/title product links, compact cart action, and upper-card quick-add overlay.
* Preserve production category description and related research product modules in source release.

= 1.2.6 =
* Standalone GitHub releases and production updater.
