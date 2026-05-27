<?php
/**
 * Single product sidebar: Related research products (WooCommerce upsells + category fallback).
 *
 * @package biopentra-loop-card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce sidebar widget ID that holds the legacy demo testimonial block.
 */
function biopentra_loop_card_demo_sidebar_widget_id(): string {
	return 'block-16';
}

/**
 * Whether block content is the legacy Garderobe demo testimonial widget.
 *
 * @param string $content Widget block HTML.
 * @return bool
 */
function biopentra_loop_card_is_demo_testimonial_block( $content ) {
	return is_string( $content ) && strpos( $content, 'New Cloth Technologies' ) !== false;
}

/**
 * Collect related product IDs for the current single product.
 *
 * @param WC_Product $product Current product.
 * @param int        $limit   Max items.
 * @return int[] Product IDs in display order.
 */
function biopentra_loop_card_get_related_product_ids( WC_Product $product, $limit = 4 ) {
	$limit      = max( 1, (int) $limit );
	$current_id = (int) $product->get_id();
	$selected   = array();
	$seen       = array( $current_id => true );

	$upsell_ids = array_map( 'intval', (array) $product->get_upsell_ids() );
	foreach ( $upsell_ids as $upsell_id ) {
		if ( count( $selected ) >= $limit ) {
			break;
		}
		if ( $upsell_id <= 0 || isset( $seen[ $upsell_id ] ) ) {
			continue;
		}
		$related = wc_get_product( $upsell_id );
		if ( ! $related || $related->get_status() !== 'publish' ) {
			continue;
		}
		$seen[ $upsell_id ] = true;
		$selected[]         = $upsell_id;
	}

	if ( count( $selected ) >= $limit ) {
		return $selected;
	}

	$term_ids = wp_get_object_terms( $current_id, 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
		return $selected;
	}

	$default_cat = (int) get_option( 'default_product_cat', 0 );
	if ( $default_cat > 0 ) {
		$term_ids = array_values( array_diff( array_map( 'intval', $term_ids ), array( $default_cat ) ) );
	}

	if ( empty( $term_ids ) ) {
		return $selected;
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit * 3,
			'orderby'                => 'menu_order title',
			'order'                  => 'ASC',
			'post__not_in'           => array_keys( $seen ),
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $term_ids,
				),
			),
		)
	);

	foreach ( $query->posts as $pid ) {
		if ( count( $selected ) >= $limit ) {
			break;
		}
		$pid = (int) $pid;
		if ( $pid <= 0 || isset( $seen[ $pid ] ) ) {
			continue;
		}
		$seen[ $pid ] = true;
		$selected[]   = $pid;
	}

	return $selected;
}

/**
 * Stock label for compact related product row.
 *
 * @param WC_Product $product Product.
 * @return string Empty when in stock.
 */
function biopentra_loop_card_related_stock_label( WC_Product $product ) {
	if ( $product->is_in_stock() ) {
		return '';
	}
	return __( 'Out of stock', 'biopentra-loop-card' );
}

/**
 * Render related research products markup.
 *
 * @param int $limit Max products.
 * @return string HTML or empty string.
 */
function biopentra_loop_card_render_related_research_products( $limit = 4 ) {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return '';
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$ids = biopentra_loop_card_get_related_product_ids( $product, $limit );
	if ( empty( $ids ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="bp-related-products" aria-labelledby="bp-related-products-title">
		<h2 id="bp-related-products-title" class="bp-related-products__title"><?php esc_html_e( 'Related research products', 'biopentra-loop-card' ); ?></h2>
		<div class="bp-related-products__list">
			<?php
			foreach ( $ids as $related_id ) {
				$related = wc_get_product( $related_id );
				if ( ! $related || $related->get_status() !== 'publish' ) {
					continue;
				}

				$url   = get_permalink( $related_id );
				$name  = $related->get_name();
				$image = $related->get_image(
					'woocommerce_thumbnail',
					array(
						'class' => 'bp-related-products__image',
						'alt'   => esc_attr( $name ),
					)
				);

				$price_html = function_exists( 'biopentra_loop_card_format_grid_price_html' )
					? biopentra_loop_card_format_grid_price_html( $related )
					: $related->get_price_html();

				$stock_label = biopentra_loop_card_related_stock_label( $related );
				?>
				<a class="bp-related-products__item" href="<?php echo esc_url( $url ); ?>">
					<?php echo wp_kses_post( $image ); ?>
					<span class="bp-related-products__content">
						<span class="bp-related-products__name"><?php echo esc_html( $name ); ?></span>
						<span class="bp-related-products__price"><?php echo wp_kses_post( $price_html ); ?></span>
						<?php if ( $stock_label !== '' ) : ?>
							<span class="bp-related-products__stock"><?php echo esc_html( $stock_label ); ?></span>
						<?php endif; ?>
						<span class="bp-related-products__link"><?php esc_html_e( 'View product', 'biopentra-loop-card' ); ?></span>
					</span>
				</a>
				<?php
			}
			?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Shortcode: [biopentra_related_research_products]
 *
 * @param array $atts Attributes.
 * @return string
 */
function biopentra_loop_card_shortcode_related_research_products( $atts ) {
	$atts = shortcode_atts(
		array(
			'limit' => 4,
		),
		$atts,
		'biopentra_related_research_products'
	);

	return biopentra_loop_card_render_related_research_products( (int) $atts['limit'] );
}
add_shortcode( 'biopentra_related_research_products', 'biopentra_loop_card_shortcode_related_research_products' );

/**
 * Replace legacy demo testimonial block in WooCommerce sidebar on single product pages.
 *
 * @param string          $content  Widget content.
 * @param array           $instance Widget instance.
 * @param WP_Widget_Block $widget   Widget object.
 * @return string
 */
function biopentra_loop_card_replace_demo_sidebar_block( $content, $instance, $widget ) {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $content;
	}

	$widget_id = ( is_object( $widget ) && isset( $widget->id ) ) ? (string) $widget->id : '';
	$is_demo   = ( $widget_id === biopentra_loop_card_demo_sidebar_widget_id() )
		|| biopentra_loop_card_is_demo_testimonial_block( $content );

	if ( ! $is_demo ) {
		return $content;
	}

	$replacement = biopentra_loop_card_render_related_research_products( 4 );
	if ( $replacement === '' ) {
		return '';
	}

	return $replacement;
}
add_filter( 'widget_block_content', 'biopentra_loop_card_replace_demo_sidebar_block', 10, 3 );

/**
 * Enqueue related products styles on single product pages.
 */
function biopentra_loop_card_enqueue_related_products_assets() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	wp_enqueue_style(
		'biopentra-related-products',
		BIOPENTRA_LOOP_CARD_URL . 'assets/related-research-products.css',
		array( 'biopentra-loop-card' ),
		BIOPENTRA_LOOP_CARD_VER
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_loop_card_enqueue_related_products_assets', 25 );
