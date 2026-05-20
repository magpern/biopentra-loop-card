<?php
/**
 * One-time (or re-runnable) setup: Shop Elementor page, SiteHome CTA link, menu, WC shop ID.
 *
 * Run from project root:
 *   ./wp eval-file wp-content/plugins/biopentra-loop-card/includes/setup-shop-page-cli.php
 *
 * @package biopentra-loop-card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param array    $nodes Elementor tree (by ref).
 * @param string   $id    Target element id.
 * @param callable $cb    function( array &$node ): void
 * @return bool Whether the node was found.
 */
function biopentra_setup_shop_walk_patch( array &$nodes, $id, $cb ) {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( isset( $node['id'] ) && $node['id'] === $id ) {
			$cb( $node );
			unset( $node );
			return true;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			if ( biopentra_setup_shop_walk_patch( $node['elements'], $id, $cb ) ) {
				unset( $node );
				return true;
			}
		}
	}
	unset( $node );
	return false;
}

$sitehome_id = (int) get_option( 'page_on_front' );
if ( ! $sitehome_id ) {
	$sitehome_id = 3483;
}

$shop_page = get_page_by_path( 'shop', OBJECT, 'page' );
if ( $shop_page ) {
	$shop_id = (int) $shop_page->ID;
	echo "Shop page exists ID {$shop_id}, will refresh layout.\n";
} else {
	$shop_id = wp_insert_post(
		array(
			'post_title'   => 'Shop',
			'post_name'    => 'shop',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		),
		true
	);
	if ( is_wp_error( $shop_id ) ) {
		echo 'ERROR creating page: ' . $shop_id->get_error_message() . "\n";
		return;
	}
	echo "Created Shop page ID {$shop_id}.\n";
}

$shop_url = get_permalink( $shop_id );

$raw = get_post_meta( $sitehome_id, '_elementor_data', true );
$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
if ( ! is_array( $data ) || ! isset( $data[0], $data[1] ) ) {
	echo "ERROR: SiteHome ({$sitehome_id}) has no Elementor data.\n";
	return;
}

$shop_data = json_decode( wp_json_encode( $data ), true );

// --- Hero (first top-level container): copy SiteHome look, shop-focused copy, remove "Shop products" CTA row.
$hero_inner = &$shop_data[0]['elements'][0]['elements'];
foreach ( $hero_inner as $hi => $wel ) {
	if ( isset( $wel['id'] ) && '048a31e' === $wel['id'] ) {
		unset( $hero_inner[ $hi ] );
		break;
	}
}
$shop_data[0]['elements'][0]['elements'] = array_values( $hero_inner );
$hero_inner = &$shop_data[0]['elements'][0]['elements'];

foreach ( $hero_inner as &$w ) {
	if ( isset( $w['id'] ) && 'bca4a96' === $w['id'] && isset( $w['settings']['title'] ) ) {
		$w['settings']['title'] = 'Research compound catalog';
	}
	if ( isset( $w['id'] ) && '49d41b0' === $w['id'] && isset( $w['settings']['editor'] ) ) {
		$w['settings']['editor'] = '<p>Browse products by category or search below. All materials are supplied for laboratory research use only and are not for human consumption.</p>';
	}
}
unset( $w );

// --- Product section: filters + loop + disclaimer.
$product_section = &$shop_data[1]['elements'][0];
$loop_settings   = null;
if ( isset( $product_section['elements'][0] ) && isset( $product_section['elements'][0]['id'] ) && 'ed52b7f' === $product_section['elements'][0]['id'] ) {
	$loop_settings = &$product_section['elements'][0]['settings'];
} else {
	foreach ( $product_section['elements'] as &$maybe ) {
		if ( isset( $maybe['id'], $maybe['widgetType'] ) && 'ed52b7f' === $maybe['id'] && 'loop-grid' === $maybe['widgetType'] ) {
			$loop_settings = &$maybe['settings'];
			break;
		}
	}
	unset( $maybe );
}

if ( ! is_array( $loop_settings ) ) {
	echo "ERROR: loop-grid ed52b7f not found in cloned data.\n";
	return;
}

$loop_settings['query_include']                  = array();
$loop_settings['query_include_term_ids']         = array();
$loop_settings['product_query_include']          = array();
$loop_settings['product_query_include_term_ids'] = array();
$loop_settings                                   = array_merge(
	$loop_settings,
	function_exists( 'biopentra_loop_card_shop_pagination_settings' )
		? biopentra_loop_card_shop_pagination_settings()
		: array(
			'pagination_type'                     => 'load_more_on_click',
			'button_text'                         => 'Load more products',
			'load_more_no_posts_message_switcher' => 'yes',
			'load_more_no_posts_custom_message'   => 'No more products',
		)
);

$search_html = function_exists( 'biopentra_loop_card_get_shop_search_form_html' )
	? biopentra_loop_card_get_shop_search_form_html()
	: '';
if ( $search_html === '' ) {
	echo "ERROR: Could not build shop search form HTML.\n";
	return;
}

$filter_row = array(
	'id'       => 'c4b3a91',
	'elType'   => 'container',
	'settings' => array(
		'content_width'          => 'full',
		'flex_direction'         => 'row',
		'flex_direction_mobile'  => 'column',
		'flex_justify_content'   => 'space-between',
		'flex_align_items'       => 'center',
		'flex_wrap'              => 'wrap',
		'flex_gap'               => array(
			'column'   => '20',
			'row'      => '16',
			'isLinked' => false,
			'unit'     => 'px',
			'size'     => 20,
		),
		'margin'                 => array(
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '0',
			'bottom'   => '28',
			'left'     => '0',
			'isLinked' => false,
		),
	),
	'elements' => array(
		array(
			'id'         => 'b3a2918',
			'elType'     => 'widget',
			'settings'   => array(
				'selected_element' => 'ed52b7f',
				'taxonomy'         => 'product_cat',
				'direction'        => 'horizontal',
			),
			'elements'   => array(),
			'widgetType' => 'taxonomy-filter',
		),
		array(
			'id'         => 'a2917d2',
			'elType'     => 'widget',
			'settings'   => array( 'html' => $search_html ),
			'elements'   => array(),
			'widgetType' => 'html',
		),
	),
	'isInner'  => true,
);

$disclaimer = array(
	'id'         => 'f2917e3',
	'elType'     => 'widget',
	'settings'   => array(
		'editor'                  => '<p><strong>Important:</strong> All products listed on this site are intended strictly for in vitro laboratory research use only. They are not medicines, food supplements, or cosmetics, and are not for human or veterinary consumption. Purchasers are responsible for lawful use and compliance with applicable regulations in their jurisdiction.</p>',
		'text_align'              => 'center',
		'typography_typography'   => 'custom',
		'typography_font_size'    => array( 'unit' => 'px', 'size' => 14, 'sizes' => array() ),
		'typography_line_height'  => array( 'unit' => 'em', 'size' => 1.55, 'sizes' => array() ),
		'_margin'                 => array(
			'unit'     => 'px',
			'top'      => '36',
			'right'    => '0',
			'bottom'   => '0',
			'left'     => '0',
			'isLinked' => false,
		),
	),
	'elements'   => array(),
	'widgetType' => 'text-editor',
);

$loop_el = null;
$loop_ix = null;
foreach ( $product_section['elements'] as $ix => $el ) {
	if ( isset( $el['id'] ) && 'ed52b7f' === $el['id'] ) {
		$loop_el = $el;
		$loop_ix = $ix;
		break;
	}
}
if ( null === $loop_ix ) {
	echo "ERROR: could not locate loop grid in section.\n";
	return;
}

$product_section['elements'] = array(
	$filter_row,
	$loop_el,
	$disclaimer,
);

$shop_json = wp_json_encode( $shop_data );

update_post_meta( $shop_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $shop_id, '_elementor_template_type', 'wp-page' );
update_post_meta( $shop_id, '_wp_page_template', 'elementor_header_footer' );
update_post_meta( $shop_id, '_elementor_data', wp_slash( $shop_json ) );

$page_css = get_post_meta( $sitehome_id, '_elementor_page_settings', true );
if ( is_string( $page_css ) || is_array( $page_css ) ) {
	update_post_meta( $shop_id, '_elementor_page_settings', $page_css );
}

delete_post_meta( $shop_id, '_elementor_css' );
delete_post_meta( $shop_id, '_elementor_element_cache' );

update_option( 'woocommerce_shop_page_id', $shop_id );

// SiteHome: link "Shop products" button.
$home_raw  = get_post_meta( $sitehome_id, '_elementor_data', true );
$home_data = is_string( $home_raw ) ? json_decode( $home_raw, true ) : null;
if ( is_array( $home_data ) ) {
	$patched = biopentra_setup_shop_walk_patch(
		$home_data,
		'3e97b89',
		static function ( array &$node ) use ( $shop_url ) {
			if ( ( $node['widgetType'] ?? '' ) !== 'button' ) {
				return;
			}
			$node['settings']['link'] = array(
				'url'               => $shop_url,
				'is_external'       => '',
				'nofollow'          => '',
				'custom_attributes' => '',
			);
		}
	);
	if ( $patched ) {
		update_post_meta( $sitehome_id, '_elementor_data', wp_slash( wp_json_encode( $home_data ) ) );
		delete_post_meta( $sitehome_id, '_elementor_css' );
		delete_post_meta( $sitehome_id, '_elementor_element_cache' );
		echo "Updated SiteHome Shop products button → {$shop_url}\n";
	} else {
		echo "WARN: button 3e97b89 not found on SiteHome; skip button link.\n";
	}
}

$menu_id = 34;
$items   = wp_get_nav_menu_items( $menu_id );
$has_shop = false;
if ( is_array( $items ) ) {
	foreach ( $items as $item ) {
		if ( isset( $item->url ) && untrailingslashit( $item->url ) === untrailingslashit( $shop_url ) ) {
			$has_shop = true;
			break;
		}
	}
}
if ( ! $has_shop ) {
	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'     => 'Shop',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $shop_id,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		)
	);
	echo "Added Shop to menu {$menu_id}.\n";
} else {
	echo "Menu already has Shop link.\n";
}

if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

flush_rewrite_rules( false );

echo "Done. Shop URL: {$shop_url}\n";
echo "Run: ./wp elementor flush_css\n";
