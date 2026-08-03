<?php
/**
 * WooCommerce content-product adapter — routes WC loops to the canonical renderer.
 *
 * Primary integration: wc_get_template_part filter (not woocommerce_locate_template).
 *
 * @package biopentra-loop-card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request should replace Blocksy/WC loop cards with the canonical renderer.
 *
 * @return bool
 */
function biopentra_loop_card_is_wc_canonical_loop_context() {
	if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
		return false;
	}

	$shop_id = (int) get_option( 'woocommerce_shop_page_id' );
	if ( $shop_id && is_page( $shop_id ) ) {
		return false;
	}

	if ( is_product_taxonomy() ) {
		return true;
	}

	if ( is_search() ) {
		$post_type = get_query_var( 'post_type' );
		if ( 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) ) {
			return true;
		}
		if ( empty( $post_type ) && get_query_var( 's' ) ) {
			return (bool) apply_filters( 'biopentra_loop_card_is_product_search', true );
		}
	}

	$loop_name = function_exists( 'wc_get_loop_prop' ) ? (string) wc_get_loop_prop( 'name', 'default' ) : 'default';
	if ( in_array( $loop_name, array( 'related', 'up-sells', 'cross-sells' ), true ) ) {
		return true;
	}

	return (bool) apply_filters( 'biopentra_loop_card_is_wc_canonical_loop_context', false );
}

/**
 * Resolve render context for the current WooCommerce loop.
 *
 * @return string Renderer context constant value.
 */
function biopentra_loop_card_resolve_wc_render_context() {
	if ( is_search() ) {
		return Biopentra_Loop_Card_Product_Card_Renderer::CONTEXT_SEARCH;
	}

	$loop_name = function_exists( 'wc_get_loop_prop' ) ? (string) wc_get_loop_prop( 'name', 'default' ) : 'default';
	switch ( $loop_name ) {
		case 'related':
			return Biopentra_Loop_Card_Product_Card_Renderer::CONTEXT_RELATED;
		case 'up-sells':
			return Biopentra_Loop_Card_Product_Card_Renderer::CONTEXT_UPSELL;
		case 'cross-sells':
			return Biopentra_Loop_Card_Product_Card_Renderer::CONTEXT_CROSS_SELL;
		default:
			return Biopentra_Loop_Card_Product_Card_Renderer::CONTEXT_ARCHIVE;
	}
}

/**
 * Path to plugin content-product template.
 *
 * @return string
 */
function biopentra_loop_card_wc_content_product_template_path() {
	return dirname( BIOPENTRA_LOOP_CARD_FILE ) . '/templates/woocommerce/content-product.php';
}

/**
 * Override WooCommerce content-product template part with the canonical adapter template.
 *
 * @param string $template Resolved template path.
 * @param string $slug     Template slug.
 * @param string $name     Template name.
 * @return string
 */
function biopentra_loop_card_wc_override_content_product_part( $template, $slug, $name ) {
	if ( 'content' !== $slug || 'product' !== $name || ! biopentra_loop_card_is_wc_canonical_loop_context() ) {
		return $template;
	}
	$plugin_template = biopentra_loop_card_wc_content_product_template_path();
	return file_exists( $plugin_template ) ? $plugin_template : $template;
}
add_filter( 'wc_get_template_part', 'biopentra_loop_card_wc_override_content_product_part', 20, 3 );

/**
 * Override direct wc_get_template( 'content-product.php' ) calls when used.
 *
 * @param string $template      Path.
 * @param string $template_name Template name.
 * @return string
 */
function biopentra_loop_card_wc_override_content_product_template( $template, $template_name ) {
	if ( 'content-product.php' !== $template_name || ! biopentra_loop_card_is_wc_canonical_loop_context() ) {
		return $template;
	}
	$plugin_template = biopentra_loop_card_wc_content_product_template_path();
	return file_exists( $plugin_template ) ? $plugin_template : $template;
}
add_filter( 'wc_get_template', 'biopentra_loop_card_wc_override_content_product_template', 20, 2 );

/**
 * Remove default WC loop item output hooks when canonical cards replace markup.
 */
function biopentra_loop_card_wc_prepare_canonical_loop() {
	if ( ! biopentra_loop_card_is_wc_canonical_loop_context() ) {
		return;
	}

	remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
}
add_action( 'woocommerce_before_shop_loop', 'biopentra_loop_card_wc_prepare_canonical_loop', 1 );

/**
 * Mark canonical WC product loops for styling/integration.
 *
 * @param string $html Loop open markup.
 * @return string
 */
function biopentra_loop_card_wc_mark_canonical_loop_start( $html ) {
	if ( ! biopentra_loop_card_is_wc_canonical_loop_context() ) {
		return $html;
	}
	if ( strpos( $html, 'biopentra-canonical-wc-loop' ) !== false ) {
		return $html;
	}
	return preg_replace(
		'/<ul class="products([^"]*)"/',
		'<ul class="products biopentra-canonical-wc-loop$1"',
		$html,
		1
	);
}
add_filter( 'woocommerce_product_loop_start', 'biopentra_loop_card_wc_mark_canonical_loop_start', 20 );

/**
 * Enqueue canonical WC loop integration styles.
 */
function biopentra_loop_card_wc_enqueue_assets() {
	if ( ! biopentra_loop_card_is_wc_canonical_loop_context() && ! is_product() ) {
		return;
	}
	wp_enqueue_style(
		'biopentra-canonical-wc-loop',
		BIOPENTRA_LOOP_CARD_URL . 'assets/canonical-wc-loop.css',
		array( 'biopentra-loop-card' ),
		BIOPENTRA_LOOP_CARD_VER
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_loop_card_wc_enqueue_assets', 21 );
