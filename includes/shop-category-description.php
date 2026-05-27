<?php
/**
 * Shop page: crawlable product category SEO descriptions synced with Elementor taxonomy filter.
 *
 * @package biopentra-loop-card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor taxonomy-filter widget ID on the shop page.
 *
 * @return string
 */
function biopentra_loop_card_shop_filter_widget_id(): string {
	return 'b3a2918';
}

/**
 * Product categories shown on /shop (non-empty description, products assigned).
 *
 * @return WP_Term[]
 */
function biopentra_loop_card_shop_category_terms(): array {
	$exclude = array();
	$default = (int) get_option( 'default_product_cat', 0 );
	if ( $default > 0 ) {
		$exclude[] = $default;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'exclude'    => $exclude,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return array();
	}

	return array_values(
		array_filter(
			$terms,
			static function ( $term ) {
				if ( ! $term instanceof WP_Term ) {
					return false;
				}
				$description = term_description( $term->term_id, 'product_cat' );
				return is_string( $description ) && trim( wp_strip_all_tags( $description ) ) !== '';
			}
		)
	);
}

/**
 * Initial active category slug from Elementor filter query string (empty = All).
 *
 * @return string
 */
function biopentra_loop_card_shop_initial_category_slug(): string {
	$loop_id = biopentra_loop_card_shop_loop_widget_id();
	$key     = 'e-filter-' . $loop_id . '-product_cat';

	if ( empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}

	$raw = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $raw === '' || '__all' === $raw ) {
		return '';
	}

	$slug = strtok( $raw, ' ' );
	if ( false === $slug || $slug === '' ) {
		return '';
	}

	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	return $term->slug;
}

/**
 * Server-rendered markup: every category description is in the HTML source for crawlers.
 *
 * @return string
 */
function biopentra_loop_card_render_category_description_markup(): string {
	$terms          = biopentra_loop_card_shop_category_terms();
	$initial_slug   = biopentra_loop_card_shop_initial_category_slug();
	$section_hidden = $initial_slug === '' ? ' hidden' : '';

	if ( empty( $terms ) ) {
		return '';
	}

	$panels = '';
	foreach ( $terms as $term ) {
		$description = term_description( $term->term_id, 'product_cat' );
		if ( ! is_string( $description ) || trim( wp_strip_all_tags( $description ) ) === '' ) {
			continue;
		}

		$panel_hidden = ( $initial_slug !== $term->slug ) ? ' hidden' : '';

		$panels .= sprintf(
			'<div class="mp-category-description__panel" data-category-slug="%1$s" id="mp-cat-desc-%1$s"%2$s>
				<div class="mp-category-description__inner">%3$s</div>
			</div>',
			esc_attr( $term->slug ),
			$panel_hidden,
			wp_kses_post( $description )
		);
	}

	if ( $panels === '' ) {
		return '';
	}

	return sprintf(
		'<section class="mp-category-description" id="mp-category-description" aria-live="polite" data-filter-widget="%1$s" data-loop-widget="%2$s"%3$s>
			<div class="mp-category-description__panels">%4$s</div>
		</section>',
		esc_attr( biopentra_loop_card_shop_filter_widget_id() ),
		esc_attr( biopentra_loop_card_shop_loop_widget_id() ),
		$section_hidden,
		$panels
	);
}

/**
 * Output description block directly above the shop loop grid.
 *
 * @param \Elementor\Widget_Base $widget Widget.
 */
function biopentra_loop_card_inject_category_description_before_loop( $widget ) {
	if ( ! biopentra_loop_card_is_shop_context() || ! is_page( (int) wc_get_page_id( 'shop' ) ) ) {
		return;
	}
	if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) || 'loop-grid' !== $widget->get_name() ) {
		return;
	}
	if ( method_exists( $widget, 'get_id' ) && biopentra_loop_card_shop_loop_widget_id() !== $widget->get_id() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer.
	echo biopentra_loop_card_render_category_description_markup();
}
add_action( 'elementor/widget/before_render_content', 'biopentra_loop_card_inject_category_description_before_loop', 5, 1 );

/**
 * Enqueue shop category description assets.
 */
function biopentra_loop_card_enqueue_shop_category_description_assets() {
	if ( ! biopentra_loop_card_is_shop_context() || ! is_page( (int) wc_get_page_id( 'shop' ) ) ) {
		return;
	}
	if ( empty( biopentra_loop_card_shop_category_terms() ) ) {
		return;
	}

	wp_enqueue_style(
		'biopentra-shop-category-description',
		BIOPENTRA_LOOP_CARD_URL . 'assets/shop-category-description.css',
		array(),
		BIOPENTRA_LOOP_CARD_VER
	);

	wp_enqueue_script(
		'biopentra-shop-category-description',
		BIOPENTRA_LOOP_CARD_URL . 'assets/shop-category-description.js',
		array( 'jquery', 'elementor-frontend' ),
		BIOPENTRA_LOOP_CARD_VER,
		true
	);

	$slugs = array_map(
		static function ( $term ) {
			return $term->slug;
		},
		biopentra_loop_card_shop_category_terms()
	);

	wp_localize_script(
		'biopentra-shop-category-description',
		'biopentraShopCategoryDescription',
		array(
			'filterWidgetId' => biopentra_loop_card_shop_filter_widget_id(),
			'loopWidgetId'   => biopentra_loop_card_shop_loop_widget_id(),
			'initialSlug'    => biopentra_loop_card_shop_initial_category_slug(),
			'categorySlugs'  => $slugs,
			'urlParam'       => 'e-filter-' . biopentra_loop_card_shop_loop_widget_id() . '-product_cat',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_loop_card_enqueue_shop_category_description_assets', 26 );
