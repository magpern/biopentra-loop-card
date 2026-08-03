<?php
/**
 * Canonical product card inside WooCommerce loops (archives, search, related, upsells, cross-sells).
 *
 * WooCommerce content-product adapter — calls the canonical renderer only.
 *
 * @package biopentra-loop-card
 * @version 1.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$render_context = function_exists( 'biopentra_loop_card_resolve_wc_render_context' )
	? biopentra_loop_card_resolve_wc_render_context()
	: Biopentra_Loop_Card_Product_Card_Renderer::CONTEXT_ARCHIVE;
?>
<li <?php wc_product_class( 'biopentra-canonical-wc-loop-item', $product ); ?>>
	<?php
	do_action( 'woocommerce_before_shop_loop_item' );
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo biopentra_loop_card_render_product_card(
		$product,
		array(
			'context' => $render_context,
		)
	);
	do_action( 'woocommerce_after_shop_loop_item' );
	?>
</li>
