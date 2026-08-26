<?php
/**
 * Feature-flagged product-card ratings (M3 / WP8).
 *
 * Uses WooCommerce public product APIs only — no Internal\* classes.
 * Does not migrate Elementor template 3608; injects rating via PHP after the
 * title widget (Elementor path) and in the fallback card renderer.
 *
 * @package biopentra-loop-card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether loop-card ratings are enabled.
 *
 * Default OFF. Prefer UPR host `enable_card_ratings` when that plugin is active;
 * otherwise the WP option `biopentra_loop_card_ratings_enabled`. Final gate is
 * the `biopentra_loop_card_ratings_enabled` filter.
 *
 * @return bool
 */
function biopentra_loop_card_ratings_enabled() {
	if ( class_exists( 'Biopentra_Upr_Host_Options' ) ) {
		$enabled = (bool) Biopentra_Upr_Host_Options::get( 'enable_card_ratings', false );
	} else {
		$enabled = (bool) get_option( 'biopentra_loop_card_ratings_enabled', false );
	}

	return (bool) apply_filters( 'biopentra_loop_card_ratings_enabled', $enabled );
}

/**
 * Minimum approved review count before a card rating is shown.
 *
 * Default 3. Host option `card_ratings_min_count` when Biopentra_Upr_Host_Options exists.
 *
 * @return int
 */
function biopentra_loop_card_ratings_min_count() {
	$min = 3;
	if ( class_exists( 'Biopentra_Upr_Host_Options' ) ) {
		$min = (int) Biopentra_Upr_Host_Options::get( 'card_ratings_min_count', 3 );
	}

	return max( 1, (int) apply_filters( 'biopentra_loop_card_ratings_min_count', $min ) );
}

/**
 * Build rating markup for a product card, or empty string when gated out.
 *
 * @param WC_Product $product Product.
 * @return string HTML (already escaped / WC-safe).
 */
function biopentra_loop_card_get_rating_html( WC_Product $product ) {
	if ( ! biopentra_loop_card_ratings_enabled() ) {
		return '';
	}

	$count = (int) $product->get_review_count();
	if ( $count < biopentra_loop_card_ratings_min_count() ) {
		return '';
	}

	$average = (float) $product->get_average_rating();
	if ( $average <= 0 ) {
		return '';
	}

	$stars = function_exists( 'wc_get_rating_html' ) ? wc_get_rating_html( $average, $count ) : '';
	if ( '' === $stars ) {
		return '';
	}

	return '<div class="biopentra-loop-card__rating">' . $stars . '</div>';
}

/**
 * Append rating after the Elementor product-title widget inside loop cards.
 *
 * Avoids rewriting template 3608; keeps layout classes stable.
 *
 * @param string                     $content Widget HTML.
 * @param \Elementor\Widget_Base|mixed $widget Widget.
 * @return string
 */
function biopentra_loop_card_append_rating_after_title( $content, $widget ) {
	if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
		return $content;
	}
	if ( 'woocommerce-product-title' !== $widget->get_name() ) {
		return $content;
	}
	if ( ! class_exists( 'WooCommerce' ) || ! biopentra_loop_card_ratings_enabled() ) {
		return $content;
	}

	$product = null;
	if ( function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product instanceof WC_Product ) {
		return $content;
	}

	$rating = biopentra_loop_card_get_rating_html( $product );
	if ( '' === $rating ) {
		return $content;
	}

	return $content . $rating;
}
add_filter( 'elementor/widget/render_content', 'biopentra_loop_card_append_rating_after_title', 25, 2 );
