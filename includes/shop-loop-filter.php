<?php
/**
 * Shop Elementor taxonomy filter: query safeguards and loop-card reinit hooks.
 *
 * @package biopentra-loop-card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loop grid widget ID on the WooCommerce shop page (Elementor).
 */
function biopentra_loop_card_shop_loop_widget_id(): string {
	return 'ed52b7f';
}

/**
 * @return bool
 */
function biopentra_loop_card_is_shop_context(): bool {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}
	$shop_id = (int) wc_get_page_id( 'shop' );
	if ( $shop_id <= 0 ) {
		return false;
	}
	if ( is_page( $shop_id ) ) {
		return true;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$post_id = 0;
		if ( isset( $_REQUEST['post_id'] ) ) {
			$post_id = absint( wp_unslash( $_REQUEST['post_id'] ) );
		}
		return $post_id === $shop_id;
	}
	return false;
}

/**
 * Strip product_cat constraints when no taxonomy filter is active (e.g. "All").
 *
 * @param array                           $query_args Query args.
 * @param \Elementor\Widget_Base|\WP_Widget $widget   Widget.
 * @return array
 */
function biopentra_loop_card_shop_clear_category_when_unfiltered( $query_args, $widget ) {
	if ( ! biopentra_loop_card_is_shop_context() ) {
		return $query_args;
	}
	if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) || 'loop-grid' !== $widget->get_name() ) {
		return $query_args;
	}
	if ( method_exists( $widget, 'get_id' ) && biopentra_loop_card_shop_loop_widget_id() !== $widget->get_id() ) {
		return $query_args;
	}

	$loop_filter = null;
	if ( class_exists( '\ElementorPro\Plugin' ) ) {
		$loop_filter = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'loop-filter' );
	}

	$widget_filters = array();
	if ( $loop_filter && method_exists( $loop_filter, 'get_query_string_filters' ) ) {
		$all = $loop_filter->get_query_string_filters();
		$wid = biopentra_loop_card_shop_loop_widget_id();
		if ( ! empty( $all[ $wid ] ) ) {
			$widget_filters = $all[ $wid ];
		}
	}

	$has_product_cat_filter = false;
	if ( ! empty( $widget_filters['taxonomy']['product_cat']['terms'] ) ) {
		$terms = $widget_filters['taxonomy']['product_cat']['terms'];
		if ( is_array( $terms ) ) {
			$terms = array_filter(
				array_map( 'strval', $terms ),
				static function ( $slug ) {
					return $slug !== '' && '__all' !== $slug;
				}
			);
			$has_product_cat_filter = count( $terms ) > 0;
		}
	}

	if ( $has_product_cat_filter ) {
		return $query_args;
	}

	if ( empty( $query_args['tax_query'] ) || ! is_array( $query_args['tax_query'] ) ) {
		return $query_args;
	}

	$query_args['tax_query'] = array_values(
		array_filter(
			$query_args['tax_query'],
			static function ( $clause ) {
				if ( ! is_array( $clause ) ) {
					return true;
				}
				return empty( $clause['taxonomy'] ) || 'product_cat' !== $clause['taxonomy'];
			}
		)
	);

	if ( empty( $query_args['tax_query'] ) ) {
		unset( $query_args['tax_query'] );
	}

	return $query_args;
}
add_filter( 'elementor/query/query_args', 'biopentra_loop_card_shop_clear_category_when_unfiltered', 120, 2 );

/**
 * Enqueue shop loop filter bridge script on the shop page.
 */
function biopentra_loop_card_enqueue_shop_loop_filter_script() {
	if ( ! biopentra_loop_card_is_shop_context() || ! is_page( (int) wc_get_page_id( 'shop' ) ) ) {
		return;
	}

	wp_enqueue_script(
		'biopentra-shop-loop-filter',
		BIOPENTRA_LOOP_CARD_URL . 'assets/shop-loop-filter.js',
		array( 'jquery', 'biopentra-loop-card', 'elementor-frontend' ),
		BIOPENTRA_LOOP_CARD_VER,
		true
	);

	wp_localize_script(
		'biopentra-shop-loop-filter',
		'biopentraShopLoopFilter',
		array(
			'loopWidgetId'      => biopentra_loop_card_shop_loop_widget_id(),
			'scrollRootMargin'  => '0px 0px 240px 0px',
			'infiniteScroll'    => true,
			'i18n'              => array(
				'loadError'      => __( 'Could not update products. Please refresh the page.', 'biopentra-loop-card' ),
				'loadMoreError'  => __( 'Could not load more products. Try again.', 'biopentra-loop-card' ),
				'noMoreProducts' => __( 'No more products', 'biopentra-loop-card' ),
				'loading'        => __( 'Loading more products…', 'biopentra-loop-card' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_loop_card_enqueue_shop_loop_filter_script', 25 );

/**
 * Pagination settings applied to the shop loop-grid widget.
 *
 * @return array<string, mixed>
 */
function biopentra_loop_card_shop_pagination_settings(): array {
	return array(
		'pagination_type'                       => 'load_more_on_click',
		'button_text'                           => __( 'Load more products', 'biopentra-loop-card' ),
		'text'                                  => __( 'Load more products', 'biopentra-loop-card' ),
		'load_more_no_posts_message_switcher'   => 'yes',
		'load_more_no_posts_custom_message'       => __( 'No more products', 'biopentra-loop-card' ),
		'load_more_spinner'                     => array(
			'value'   => 'fas fa-spinner',
			'library' => 'fa-solid',
		),
	);
}

/**
 * Merge pagination settings into the shop loop-grid node.
 *
 * @param array<int, array<string, mixed>> $nodes Elementor tree.
 * @return bool
 */
function biopentra_loop_card_patch_shop_loop_pagination( array &$nodes ): bool {
	$widget_id = biopentra_loop_card_shop_loop_widget_id();
	$settings  = biopentra_loop_card_shop_pagination_settings();

	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['id'] ?? '' ) === $widget_id && ( $node['widgetType'] ?? '' ) === 'loop-grid' ) {
			$node['settings'] = array_merge( $node['settings'] ?? array(), $settings );
			unset( $node );
			return true;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			if ( biopentra_loop_card_patch_shop_loop_pagination( $node['elements'] ) ) {
				unset( $node );
				return true;
			}
		}
	}
	unset( $node );

	return false;
}

/**
 * Persist shop loop-grid pagination into Elementor page data.
 *
 * @return bool
 */
function biopentra_loop_card_sync_shop_loop_pagination(): bool {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}
	$shop_id = (int) wc_get_page_id( 'shop' );
	if ( $shop_id <= 0 ) {
		return false;
	}
	$raw = get_post_meta( $shop_id, '_elementor_data', true );
	if ( ! is_string( $raw ) || $raw === '' ) {
		return false;
	}
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		return false;
	}
	if ( ! biopentra_loop_card_patch_shop_loop_pagination( $data ) ) {
		return false;
	}
	update_post_meta( $shop_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	delete_post_meta( $shop_id, '_elementor_css' );
	delete_post_meta( $shop_id, '_elementor_element_cache' );
	return true;
}

/**
 * One-time upgrade: enable load-more / infinite-scroll on the shop loop grid.
 */
function biopentra_loop_card_maybe_enable_shop_pagination(): void {
	if ( get_option( 'biopentra_loop_card_shop_pagination_v1' ) ) {
		return;
	}
	if ( biopentra_loop_card_sync_shop_loop_pagination() ) {
		update_option( 'biopentra_loop_card_shop_pagination_v1', 1 );
	}
}
add_action( 'init', 'biopentra_loop_card_maybe_enable_shop_pagination', 35 );
