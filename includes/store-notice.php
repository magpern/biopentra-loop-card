<?php
/**
 * WooCommerce store notice: themed stylesheet (separate from loop-card.css),
 * in-flow layout, and markup without the dismiss link.
 *
 * Do not edit plugins/woocommerce/assets/css/woocommerce.css — it is replaced
 * on every WooCommerce update; overrides belong in the theme or this plugin.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $html   Default markup from WooCommerce (unused).
 * @param string $notice Notice HTML / text from settings.
 * @return string
 */
function biopentra_store_notice_markup( $html, $notice ) {
	if ( ! is_string( $notice ) ) {
		$notice = '';
	}
	if ( $notice === '' ) {
		$notice = __( 'This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woocommerce' );
	}
	$notice_id = md5( $notice );

	return '<p role="complementary" aria-label="' . esc_attr__( 'Store notice', 'woocommerce' ) . '" class="woocommerce-store-notice demo_store" data-notice-id="' . esc_attr( $notice_id ) . '" style="display:none;">' . wp_kses_post( $notice ) . '</p>';
}
add_filter( 'woocommerce_demo_store', 'biopentra_store_notice_markup', 10, 2 );

/**
 * Register and enqueue after WooCommerce base styles so layout overrides win.
 */
function biopentra_store_notice_enqueue_assets() {
	if ( ! function_exists( 'is_store_notice_showing' ) || ! is_store_notice_showing() ) {
		return;
	}

	$deps = array();
	if ( wp_style_is( 'woocommerce-general', 'registered' ) ) {
		$deps[] = 'woocommerce-general';
	}

	wp_register_style(
		'biopentra-store-notice',
		BIOPENTRA_LOOP_CARD_URL . 'assets/store-notice.css',
		$deps,
		BIOPENTRA_LOOP_CARD_VER
	);
	wp_enqueue_style( 'biopentra-store-notice' );
}
add_action( 'wp_enqueue_scripts', 'biopentra_store_notice_enqueue_assets', 30 );
