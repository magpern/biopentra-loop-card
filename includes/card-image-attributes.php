<?php
/**
 * Milestone D1 — product card image sizes + LCP hints.
 *
 * @package Biopentra_Loop_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical card `sizes` for 2/3/4-column commercial grids.
 *
 * @return string
 */
function biopentra_loop_card_image_sizes_attr() {
	/**
	 * Filter the sizes attribute for loop card featured images.
	 *
	 * @param string $sizes sizes attribute value.
	 */
	return (string) apply_filters(
		'biopentra_loop_card_image_sizes',
		'(max-width: 480px) 45vw, (max-width: 768px) 30vw, 22vw'
	);
}

/**
 * Whether the current request is a commercial product-listing surface.
 *
 * @return bool
 */
function biopentra_loop_card_is_commercial_image_context() {
	if ( is_admin() ) {
		return false;
	}

	if ( is_front_page() || is_shop() || is_product_taxonomy() || is_product() ) {
		return true;
	}

	if ( is_search() && isset( $_GET['post_type'] ) && 'product' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return true;
	}

	if ( is_page() && function_exists( 'biopentra_storefront_seo_category_configs' ) ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		$cfgs = biopentra_storefront_seo_category_configs();
		if ( ! empty( $cfgs[ $slug ] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Incrementing counter for commercial product featured images in this request.
 *
 * @return int Current 1-based index after increment.
 */
function biopentra_loop_card_next_commercial_image_index() {
	static $card_image_index = 0;
	++$card_image_index;
	return $card_image_index;
}

/**
 * Apply sizes / lazy / fetchpriority on product featured images in commercial loops.
 *
 * @param array        $attr       Attributes.
 * @param WP_Post      $attachment Attachment.
 * @param string|int[] $size       Size.
 * @return array
 */
function biopentra_loop_card_filter_attachment_image_attributes( $attr, $attachment, $size ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	if ( ! biopentra_loop_card_is_commercial_image_context() ) {
		return $attr;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post || 'product' !== $post->post_type ) {
		return $attr;
	}

	$attr['sizes'] = biopentra_loop_card_image_sizes_attr();

	$index = biopentra_loop_card_next_commercial_image_index();

	if ( 1 === $index ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$attr['decoding']      = 'async';
	} else {
		$attr['loading'] = 'lazy';
		unset( $attr['fetchpriority'] );
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'biopentra_loop_card_filter_attachment_image_attributes', 999, 3 );

/**
 * Ask core to omit loading=lazy on the first few content images (LCP).
 *
 * @param int $threshold Default threshold.
 * @return int
 */
function biopentra_loop_card_omit_loading_attr_threshold( $threshold ) {
	if ( biopentra_loop_card_is_commercial_image_context() ) {
		return max( (int) $threshold, 3 );
	}
	return (int) $threshold;
}
add_filter( 'wp_omit_loading_attr_threshold', 'biopentra_loop_card_omit_loading_attr_threshold' );

/**
 * Force high fetchpriority on the first commercial product image attributes.
 *
 * @param array  $loading_attrs Attributes for loading optimization.
 * @param string $tag_name      Tag name.
 * @return array
 */
function biopentra_loop_card_filter_loading_optimization_attributes( $loading_attrs, $tag_name ) {
	static $high_applied = false;

	if ( 'img' !== $tag_name || ! biopentra_loop_card_is_commercial_image_context() || $high_applied ) {
		return $loading_attrs;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post || 'product' !== $post->post_type ) {
		return $loading_attrs;
	}

	$high_applied                   = true;
	$loading_attrs['loading']       = 'eager';
	$loading_attrs['fetchpriority'] = 'high';
	return $loading_attrs;
}
add_filter( 'wp_get_loading_optimization_attributes', 'biopentra_loop_card_filter_loading_optimization_attributes', 20, 2 );

/**
 * Final HTML pass for Elementor featured images in commercial loops.
 *
 * @param string                 $content Widget HTML.
 * @param \Elementor\Widget_Base $widget  Widget.
 * @return string
 */
function biopentra_loop_card_filter_elementor_featured_image_html( $content, $widget ) {
	if ( ! biopentra_loop_card_is_commercial_image_context() || ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
		return $content;
	}

	$name = $widget->get_name();
	if ( 'theme-post-featured-image' !== $name && 'image' !== $name ) {
		return $content;
	}

	if ( false === strpos( $content, '<img' ) ) {
		return $content;
	}

	static $rewrote_first = false;
	$sizes                = biopentra_loop_card_image_sizes_attr();

	$content = preg_replace_callback(
		'/<img\b[^>]*>/i',
		static function ( $m ) use ( &$rewrote_first, $sizes ) {
			$tag = $m[0];

			if ( preg_match( '/\bsizes=(["\']).*?\1/i', $tag ) ) {
				$tag = preg_replace( '/\bsizes=(["\']).*?\1/i', 'sizes=$1' . $sizes . '$1', $tag, 1 );
			} else {
				$tag = rtrim( substr( $tag, 0, -1 ) ) . ' sizes="' . esc_attr( $sizes ) . '">';
			}

			if ( ! $rewrote_first ) {
				$rewrote_first = true;
				if ( preg_match( '/\bloading=/i', $tag ) ) {
					$tag = preg_replace( '/\bloading=(["\']).*?\1/i', 'loading=$1eager$1', $tag );
				} else {
					$tag = rtrim( substr( $tag, 0, -1 ) ) . ' loading="eager">';
				}
				if ( preg_match( '/\bfetchpriority=/i', $tag ) ) {
					$tag = preg_replace( '/\bfetchpriority=(["\']).*?\1/i', 'fetchpriority=$1high$1', $tag );
				} else {
					$tag = rtrim( substr( $tag, 0, -1 ) ) . ' fetchpriority="high">';
				}
				// Drop sizes=auto prefix if present — conflicts with eager LCP.
				$tag = preg_replace( '/\bsizes=(["\'])auto,\s*/i', 'sizes=$1', $tag );
			}

			return $tag;
		},
		$content,
		1
	);

	return is_string( $content ) ? $content : $content;
}
add_filter( 'elementor/widget/render_content', 'biopentra_loop_card_filter_elementor_featured_image_html', 20, 2 );
