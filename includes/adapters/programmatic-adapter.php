<?php
/**
 * Programmatic adapter — ordered product ID lists call the canonical renderer.
 *
 * @package biopentra-loop-card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a grid/list of canonical cards for explicit product IDs.
 *
 * @param int[] $product_ids Product IDs in display order.
 * @param array $args        context, layout, wrapper_class, template_id.
 * @return string HTML.
 */
function biopentra_loop_card_render_product_cards( array $product_ids, array $args = array() ) {
	$product_ids = array_values( array_filter( array_map( 'intval', $product_ids ) ) );
	if ( empty( $product_ids ) ) {
		return '';
	}

	if ( ! isset( $args['context'] ) ) {
		$args['context'] = Biopentra_Loop_Card_Product_Card_Renderer::CONTEXT_FUTURE;
	}

	$layout  = isset( $args['layout'] ) ? (string) $args['layout'] : 'grid';
	$classes = array( 'biopentra-canonical-product-grid' );
	$classes[] = 'compact-list' === $layout ? 'biopentra-canonical-product-grid--compact' : 'biopentra-canonical-product-grid--grid';

	ob_start();
	echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
	foreach ( $product_ids as $pid ) {
		$product = wc_get_product( $pid );
		if ( $product instanceof WC_Product ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo biopentra_loop_card_render_product_card( $product, $args );
		}
	}
	echo '</div>';
	return (string) ob_get_clean();
}
