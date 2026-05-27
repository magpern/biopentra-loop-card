<?php
/**
 * Plugin Name: Biopentra Loop Card
 * Description: Elementor Loop Grid: hover overlay (variation + AJAX add to cart), stock banners, card navigation.
 * Version: 1.3.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: biopentra-loop-card
 *
 * Install: copy this folder to wp-content/plugins/biopentra-loop-card and activate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BIOPENTRA_LOOP_CARD_FILE' ) ) {
	define( 'BIOPENTRA_LOOP_CARD_FILE', __FILE__ );
}

require_once __DIR__ . '/includes/age-gate-confirm-fix.php';
require_once __DIR__ . '/includes/store-notice.php';

define( 'BIOPENTRA_LOOP_CARD_URL', plugin_dir_url( __FILE__ ) );
define( 'BIOPENTRA_LOOP_CARD_VER', '1.3.1' );

/**
 * GitHub Release updater (admin / cron only).
 */
function biopentra_loop_card_init_github_updater() {
	if ( ! is_admin() && ! ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
		return;
	}

	require_once __DIR__ . '/includes/class-github-updater.php';
	Biopentra_Loop_Card_Github_Updater::maybe_init();
}
add_action( 'plugins_loaded', 'biopentra_loop_card_init_github_updater', 9 );

require_once __DIR__ . '/includes/shop-loop-filter.php';
require_once __DIR__ . '/includes/shop-category-description.php';
require_once __DIR__ . '/includes/related-research-products.php';

/**
 * Loop grid price: "from X" when multiple variation prices; single price when one variation or equal prices.
 *
 * @param WC_Product $product Product.
 * @return string HTML.
 */
function biopentra_loop_card_format_grid_price_html( WC_Product $product ) {
	if ( $product->is_type( 'simple' ) || $product->is_type( 'variation' ) ) {
		return wc_price( wc_get_price_to_display( $product ) );
	}

	if ( ! $product->is_type( 'variable' ) ) {
		return $product->get_price_html();
	}

	$children = $product->get_children();
	$published = array();
	foreach ( $children as $cid ) {
		$v = wc_get_product( $cid );
		if ( $v && $v->get_status() === 'publish' ) {
			$published[] = $v;
		}
	}

	if ( count( $published ) <= 1 ) {
		if ( count( $published ) === 1 ) {
			return wc_price( wc_get_price_to_display( $published[0] ) );
		}
		return wc_price( wc_get_price_to_display( $product ) );
	}

	$min_raw = (float) $product->get_variation_price( 'min', true );
	$max_raw = (float) $product->get_variation_price( 'max', true );

	if ( $min_raw <= 0 && $max_raw <= 0 ) {
		return $product->get_price_html();
	}

	if ( abs( $min_raw - $max_raw ) < 0.00001 ) {
		return wc_price( wc_get_price_to_display( $product, array( 'price' => (string) $min_raw ) ) );
	}

	$min_display = wc_get_price_to_display( $product, array( 'price' => (string) $min_raw ) );

	return sprintf(
		/* translators: %s: formatted product price */
		__( 'from %s', 'biopentra-loop-card' ),
		wc_price( $min_display )
	);
}

/**
 * @param string     $price   Original HTML.
 * @param WC_Product $product Product.
 * @return string
 */
function biopentra_loop_card_filter_loop_price_html( $price, $product ) {
	if ( empty( $GLOBALS['biopentra_loop_card_price_scope'] ) || ! $product instanceof WC_Product ) {
		return $price;
	}
	return '<span class="price">' . biopentra_loop_card_format_grid_price_html( $product ) . '</span>';
}

/**
 * Scope custom price HTML to Elementor product price widget outside single product pages.
 *
 * @param \Elementor\Widget_Base $widget Widget.
 */
function biopentra_loop_card_before_price_widget( $widget ) {
	if ( $widget->get_name() !== 'woocommerce-product-price' ) {
		return;
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		return;
	}
	if ( get_post_type() !== 'product' ) {
		return;
	}
	$GLOBALS['biopentra_loop_card_price_scope'] = true;
}

/**
 * @param string                 $content Markup.
 * @param \Elementor\Widget_Base $widget  Widget.
 * @return string
 */
function biopentra_loop_card_after_price_widget_content( $content, $widget ) {
	if ( $widget->get_name() === 'woocommerce-product-price' ) {
		unset( $GLOBALS['biopentra_loop_card_price_scope'] );
	}
	return $content;
}

add_action( 'elementor/widget/before_render_content', 'biopentra_loop_card_before_price_widget', 10, 1 );
add_filter( 'woocommerce_get_price_html', 'biopentra_loop_card_filter_loop_price_html', 50, 2 );
add_filter( 'elementor/widget/render_content', 'biopentra_loop_card_after_price_widget_content', 999, 2 );

function biopentra_loop_card_build_payload( WC_Product $product ) {
	$pid        = $product->get_id();
	$permalink  = get_permalink( $pid );
	$type       = $product->get_type();
	$variations = array();

	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_available_variations() as $row ) {
			$attrs = isset( $row['attributes'] ) && is_array( $row['attributes'] ) ? $row['attributes'] : array();
			$label = '';
			foreach ( $attrs as $val ) {
				if ( $val !== '' && $val !== null ) {
					$label = (string) $val;
					break;
				}
			}
			if ( $label === '' ) {
				$label = '#' . (int) $row['variation_id'];
			}
			$variations[] = array(
				'variation_id' => (int) $row['variation_id'],
				'attributes'   => $attrs,
				'label'        => $label,
				'in_stock'     => ! empty( $row['is_in_stock'] ),
				'price_html'   => isset( $row['price_html'] ) ? $row['price_html'] : '',
			);
		}
	}

	return array(
		'product_id'   => $pid,
		'permalink'    => $permalink,
		'type'         => $type,
		'price_html'   => biopentra_loop_card_format_grid_price_html( $product ),
		'variations'   => $variations,
		'any_in_stock' => (bool) $product->is_in_stock(),
		'is_variable'  => $product->is_type( 'variable' ),
	);
}

function biopentra_loop_card_document_wrapper_attributes( $attributes, $document ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return $attributes;
	}
	if ( ! is_a( $document, '\ElementorPro\Modules\LoopBuilder\Documents\Loop' ) ) {
		return $attributes;
	}
	$post_id = get_the_ID();
	if ( ! $post_id || get_post_type( $post_id ) !== 'product' ) {
		return $attributes;
	}
	$product = wc_get_product( $post_id );
	if ( ! $product ) {
		return $attributes;
	}
	$attributes['class']                  = isset( $attributes['class'] ) ? $attributes['class'] . ' biopentra-loop-card-root' : 'biopentra-loop-card-root';
	$attributes['data-biopentra-product'] = wp_json_encode( biopentra_loop_card_build_payload( $product ) );
	return $attributes;
}
add_filter( 'elementor/document/wrapper_attributes', 'biopentra_loop_card_document_wrapper_attributes', 20, 2 );

function biopentra_loop_card_enqueue_assets() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	wp_register_style( 'biopentra-loop-card', BIOPENTRA_LOOP_CARD_URL . 'assets/loop-card.css', array(), BIOPENTRA_LOOP_CARD_VER );
	wp_register_script( 'biopentra-loop-card', BIOPENTRA_LOOP_CARD_URL . 'assets/loop-card.js', array( 'jquery', 'wc-cart-fragments' ), BIOPENTRA_LOOP_CARD_VER, true );
	wp_register_script(
		'biopentra-shop-live-search',
		BIOPENTRA_LOOP_CARD_URL . 'assets/shop-live-search.js',
		array(),
		BIOPENTRA_LOOP_CARD_VER,
		true
	);
	wp_enqueue_style( 'biopentra-loop-card' );
	wp_enqueue_script( 'biopentra-loop-card' );
	$shop_id = (int) get_option( 'woocommerce_shop_page_id' );
	if ( $shop_id && is_page( $shop_id ) ) {
		wp_enqueue_script( 'biopentra-shop-live-search' );
		wp_localize_script(
			'biopentra-shop-live-search',
			'biopentraShopSearch',
			array(
				'restSearch'  => rest_url( 'wp/v2/search' ),
				'minLen'      => 2,
				'debounceMs'  => 280,
				'i18n'        => array(
					'searching'         => __( 'Searching…', 'biopentra-loop-card' ),
					'noResults'         => __( 'No matching products', 'biopentra-loop-card' ),
					'searchError'       => __( 'Search is temporarily unavailable', 'biopentra-loop-card' ),
					'suggestionsLabel'  => __( 'Product suggestions', 'biopentra-loop-card' ),
				),
			)
		);
	}

	wp_localize_script(
		'biopentra-loop-card',
		'biopentraLoopCard',
		array(
			'wc_ajax_url' => WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'i18n'        => array(
				'strength'    => __( 'Strength:', 'biopentra-loop-card' ),
				'clear'       => __( 'Clear', 'biopentra-loop-card' ),
				'close'       => __( 'Close', 'biopentra-loop-card' ),
				'addToCart'   => __( 'Add to cart', 'biopentra-loop-card' ),
				'outOfStock'  => __( 'Out of stock', 'biopentra-loop-card' ),
				'adding'      => __( 'Adding…', 'biopentra-loop-card' ),
				'chooseFirst' => __( 'Choose an option', 'biopentra-loop-card' ),
				'viewProduct' => __( 'View product', 'biopentra-loop-card' ),
				'quickShop'   => __( 'Quick add', 'biopentra-loop-card' ),
				'back'        => __( 'Back', 'biopentra-loop-card' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_loop_card_enqueue_assets', 20 );

/**
 * On the Shop page, merge the URL search string into Elementor Loop Grid product queries.
 *
 * @param array                           $query_args WP_Query args.
 * @param \Elementor\Widget_Base|\WP_Widget $widget   Elementor widget instance.
 * @return array
 */
function biopentra_loop_card_shop_page_search_in_loop_query( $query_args, $widget ) {
	$shop_id = (int) get_option( 'woocommerce_shop_page_id' );
	if ( ! $shop_id || ! is_page( $shop_id ) || empty( $_GET['s'] ) ) {
		return $query_args;
	}
	if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
		return $query_args;
	}
	if ( 'loop-grid' !== $widget->get_name() ) {
		return $query_args;
	}
	$query_args['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );

	return $query_args;
}
add_filter( 'elementor/query/query_args', 'biopentra_loop_card_shop_page_search_in_loop_query', 25, 2 );

/**
 * Markup for the shop product search form (Elementor HTML widget). No submit button — Enter filters the grid; live suggestions are JS-driven.
 *
 * @return string HTML or empty if WooCommerce is unavailable.
 */
function biopentra_loop_card_get_shop_search_form_html() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return '';
	}
	$shop_id = (int) wc_get_page_id( 'shop' );
	if ( $shop_id <= 0 ) {
		return '';
	}
	$url = get_permalink( $shop_id );
	if ( ! $url ) {
		return '';
	}

	return sprintf(
		'<form class="biopentra-shop-search" role="search" method="get" action="%s">
	<label class="screen-reader-text" for="biopentra-shop-s">%s</label>
	<input id="biopentra-shop-s" type="search" name="s" value="" placeholder="%s" autocomplete="off" enterkeyhint="search" aria-describedby="biopentra-shop-s-hint" />
	<span id="biopentra-shop-s-hint" class="screen-reader-text">%s</span>
	<input type="hidden" name="post_type" value="product" />
</form>',
		esc_url( $url ),
		esc_attr__( 'Search products', 'biopentra-loop-card' ),
		esc_attr__( 'Search products…', 'biopentra-loop-card' ),
		esc_html__( 'Press Enter to filter products below.', 'biopentra-loop-card' )
	);
}

/**
 * @param array $nodes Elementor elements tree (by ref).
 * @return bool Whether a matching HTML widget was updated.
 */
function biopentra_loop_card_patch_shop_search_html_widget( array &$nodes ) {
	$markup = biopentra_loop_card_get_shop_search_form_html();
	if ( $markup === '' ) {
		return false;
	}
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['widgetType'] ?? '' ) === 'html' && isset( $node['settings']['html'] ) && is_string( $node['settings']['html'] ) && strpos( $node['settings']['html'], 'biopentra-shop-search' ) !== false ) {
			$node['settings']['html'] = $markup;
			unset( $node );
			return true;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			if ( biopentra_loop_card_patch_shop_search_html_widget( $node['elements'] ) ) {
				unset( $node );
				return true;
			}
		}
	}
	unset( $node );
	return false;
}

/**
 * Persist updated shop search HTML into the WooCommerce shop page Elementor data.
 *
 * @return bool True if the document was updated.
 */
function biopentra_loop_card_sync_shop_page_search_form() {
	$shop_id = (int) get_option( 'woocommerce_shop_page_id' );
	if ( ! $shop_id ) {
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
	if ( ! biopentra_loop_card_patch_shop_search_html_widget( $data ) ) {
		return false;
	}
	update_post_meta( $shop_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	delete_post_meta( $shop_id, '_elementor_css' );
	delete_post_meta( $shop_id, '_elementor_element_cache' );
	return true;
}

/**
 * Resolve when the main query must be a real WordPress page (not WC's product archive):
 * 1) Elementor editor iframe: ?elementor-preview={page_id}
 * 2) Public visit to the WooCommerce shop permalink when that page is built with Elementor.
 *
 * WooCommerce otherwise keeps `archive-product.php` and a product main loop on /shop/, so
 * the storefront never runs `the_content()` for the Elementor-built page.
 *
 * @param \WP_Query $query Main query.
 * @return int Page post ID to force, or 0.
 */
function biopentra_loop_card_get_page_id_for_singular_shop_query( $query ) {
	if ( ! empty( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$preview_id = absint( wp_unslash( $_GET['elementor-preview'] ) );
		if ( $preview_id && 'page' === get_post_type( $preview_id ) ) {
			return $preview_id;
		}
		return 0;
	}

	if ( is_admin() || ! $query->is_main_query() ) {
		return 0;
	}

	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return 0;
	}

	$shop_id = (int) wc_get_page_id( 'shop' );
	if ( $shop_id <= 0 || get_post_meta( $shop_id, '_elementor_edit_mode', true ) !== 'builder' ) {
		return 0;
	}

	$req = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( ! is_string( $req ) || $req === '' ) {
		return 0;
	}

	$path = wp_parse_url( $req, PHP_URL_PATH );
	if ( ! is_string( $path ) ) {
		return 0;
	}

	$shop_url = get_permalink( $shop_id );
	if ( ! $shop_url ) {
		return 0;
	}

	$shop_path = untrailingslashit( (string) wp_parse_url( $shop_url, PHP_URL_PATH ) );
	$cur       = untrailingslashit( $path );

	if ( $shop_path === '' || $cur === '' ) {
		return 0;
	}

	if ( $cur !== $shop_path ) {
		return 0;
	}

	return $shop_id;
}

/**
 * @param \WP_Query $query Main query.
 */
function biopentra_loop_card_force_singular_page_for_elementor_shop( $query ) {
	$page_id = biopentra_loop_card_get_page_id_for_singular_shop_query( $query );
	if ( ! $page_id ) {
		return;
	}

	$query->set( 'post_type', 'page' );
	$query->set( 'page_id', $page_id );
	$query->set( 'pagename', '' );
	$query->set( 'name', '' );
	$query->set( 'wc_query', '' );
	$query->set( 'post__in', array() );
	$query->set( 'tax_query', array() );
	$query->set( 'meta_query', array() );

	$query->is_archive             = false;
	$query->is_post_type_archive   = false;
	$query->is_tax                 = false;
	$query->is_embed               = false;
	$query->is_page                = true;
	$query->is_singular            = true;
	$query->is_home                = false;
	$query->is_404                 = false;
}
add_action( 'pre_get_posts', 'biopentra_loop_card_force_singular_page_for_elementor_shop', 9999 );

function biopentra_loop_card_upgrade_elementor_template() {
	$tpl_id = (int) apply_filters( 'biopentra_loop_card_template_post_id', 3608 );
	if ( ! $tpl_id || get_post_type( $tpl_id ) !== 'elementor_library' ) {
		return;
	}
	$raw  = get_post_meta( $tpl_id, '_elementor_data', true );
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || ! isset( $data[0] ) ) {
		return;
	}
	$title_dyn             = '[elementor-tag id="" name="woocommerce-product-title-tag" settings="%7B%7D"]';
	$data[0]['elements'] = array(
		array(
			'id'         => '5039c87',
			'elType'     => 'widget',
			'settings'   => array(
				'image_size'             => 'woocommerce_single',
				'height'                 => array( 'size' => 196, 'unit' => 'px' ),
				'object-fit'             => 'cover',
				'width'                  => array( 'size' => 100, 'unit' => '%' ),
				'_css_classes' => 'biopentra-loop-card-img',
				'__dynamic__'  => array(
					'image' => '[elementor-tag id="" name="post-featured-image" settings="%7B%22fallback%22%3A%7B%22url%22%3A%22%22%2C%22id%22%3A%22%22%2C%22size%22%3A%22%22%7D%7D"]',
				),
			),
			'elements'   => array(),
			'widgetType' => 'theme-post-featured-image',
		),
		array(
			'id'         => 'f2a9c81',
			'elType'     => 'widget',
			'settings'   => array(
				'title'                  => '',
				'header_size'            => 'h3',
				'align'                  => 'center',
				'title_color'            => '#0F1F33',
				'typography_typography'  => 'custom',
				'typography_font_size'   => array( 'size' => 17, 'unit' => 'px' ),
				'typography_font_weight' => '600',
				'_margin'                => array(
					'unit'     => 'px',
					'top'      => '12',
					'right'    => '18',
					'bottom'   => '2',
					'left'     => '18',
					'isLinked' => false,
				),
				'__dynamic__'            => array( 'title' => $title_dyn ),
			),
			'elements'   => array(),
			'widgetType' => 'woocommerce-product-title',
		),
		array(
			'id'         => 'e7b3d44',
			'elType'     => 'widget',
			'settings'   => array(
				'text_align' => 'center',
				'_margin'    => array(
					'unit'     => 'px',
					'top'      => '0',
					'right'    => '18',
					'bottom'   => '10',
					'left'     => '18',
					'isLinked' => false,
				),
			),
			'elements'   => array(),
			'widgetType' => 'woocommerce-product-price',
		),
	);
	update_post_meta( $tpl_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	delete_post_meta( $tpl_id, '_elementor_element_cache' );
	delete_post_meta( $tpl_id, '_elementor_css' );
}

function biopentra_loop_card_maybe_upgrade_elementor_template() {
	if ( ! get_option( 'biopentra_loop_card_tpl_v1' ) ) {
		biopentra_loop_card_upgrade_elementor_template();
		update_option( 'biopentra_loop_card_tpl_v1', 1 );
	}
	if ( ! get_option( 'biopentra_loop_card_tpl_v2' ) ) {
		biopentra_loop_card_upgrade_elementor_template();
		update_option( 'biopentra_loop_card_tpl_v2', 1 );
	}
}
add_action( 'init', 'biopentra_loop_card_maybe_upgrade_elementor_template', 30 );
