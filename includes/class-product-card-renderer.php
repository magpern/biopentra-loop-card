<?php
/**
 * Canonical product card renderer — single source of truth for all listing surfaces.
 *
 * @package biopentra-loop-card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Elementor loop template 3608 (or fallback) for one product.
 */
final class Biopentra_Loop_Card_Product_Card_Renderer {

	public const VERSION = '1.0.0';

	public const CONTEXT_HOMEPAGE   = 'homepage';
	public const CONTEXT_SHOP       = 'shop';
	public const CONTEXT_SEO_PAGE   = 'seo_page';
	public const CONTEXT_ARCHIVE    = 'archive';
	public const CONTEXT_SEARCH     = 'search';
	public const CONTEXT_RELATED    = 'related';
	public const CONTEXT_UPSELL     = 'upsell';
	public const CONTEXT_CROSS_SELL = 'cross_sell';
	public const CONTEXT_SHORTCODE  = 'shortcode';
	public const CONTEXT_FUTURE     = 'future';

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Default Elementor loop item template post ID.
	 *
	 * @return int
	 */
	public function get_template_post_id() {
		return (int) apply_filters( 'biopentra_loop_card_template_post_id', 3608 );
	}

	/**
	 * Whether Elementor loop rendering is available.
	 *
	 * @param int|null $template_id Template post ID.
	 * @return bool
	 */
	public function can_render_elementor_loop( $template_id = null ) {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! class_exists( '\ElementorPro\Modules\LoopBuilder\Documents\Loop' ) ) {
			return false;
		}
		$template_id = null === $template_id ? $this->get_template_post_id() : (int) $template_id;
		if ( $template_id <= 0 || get_post_type( $template_id ) !== 'elementor_library' ) {
			return false;
		}
		return (bool) \Elementor\Plugin::$instance->documents->get( $template_id );
	}

	/**
	 * Render one canonical product card.
	 *
	 * @param WC_Product $product Product.
	 * @param array      $args    context, layout, wrapper_class, template_id.
	 * @return string HTML.
	 */
	public function render_product_card( WC_Product $product, array $args = array() ) {
		if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
			return '';
		}

		$args = apply_filters( 'biopentra_loop_card_render_args', $args, $product );

		$context     = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : '';
		$context     = (string) apply_filters( 'biopentra_loop_card_render_context', $context, $product, $args );
		$template_id = isset( $args['template_id'] ) ? (int) $args['template_id'] : $this->get_template_post_id();
		$layout      = $this->resolve_layout( $context, $args );
		$extra_class = isset( $args['wrapper_class'] ) ? (string) $args['wrapper_class'] : '';

		if ( ! $this->can_render_elementor_loop( $template_id ) ) {
			return $this->render_fallback_product_card( $product, $context );
		}

		$this->enqueue_loop_template_css( $template_id );

		global $post, $wp_query;
		$previous_post = $post;
		$product_id    = (int) $product->get_id();
		$post          = get_post( $product_id );
		if ( ! $post ) {
			return $this->render_fallback_product_card( $product, $context );
		}

		setup_postdata( $post );
		if ( is_object( $wp_query ) ) {
			$wp_query->is_loop_widget = true;
		}

		$item_classes = array(
			'e-loop-item',
			'e-loop-item-' . $product_id,
			'biopentra-canonical-loop-item',
		);
		if ( 'compact-list' === $layout ) {
			$item_classes[] = 'biopentra-canonical-loop-item--compact';
		}
		if ( $extra_class !== '' ) {
			$item_classes[] = $extra_class;
		}

		$wrapper_attrs = array(
			'class' => implode( ' ', $item_classes ),
		);
		if ( $context !== '' ) {
			$wrapper_attrs['data-biopentra-render-context'] = $context;
		}

		ob_start();
		echo '<div';
		foreach ( $wrapper_attrs as $attr => $value ) {
			echo ' ' . esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
		}
		echo '>';
		$this->print_loop_dynamic_css( $product_id, $template_id );

		$document = \Elementor\Plugin::$instance->documents->get( $template_id );
		if ( $document ) {
			$document->print_content();
		}
		echo '</div>';
		$html = (string) ob_get_clean();

		if ( is_object( $wp_query ) ) {
			unset( $wp_query->is_loop_widget );
		}
		wp_reset_postdata();
		$post = $previous_post;

		return $html;
	}

	/**
	 * Resolve layout from explicit args or render context.
	 *
	 * @param string $context Render context.
	 * @param array  $args    Renderer args.
	 * @return string grid|compact-list
	 */
	private function resolve_layout( $context, array $args ) {
		if ( isset( $args['layout'] ) ) {
			return (string) $args['layout'];
		}
		if ( in_array( $context, array( self::CONTEXT_SHORTCODE ), true ) ) {
			return 'compact-list';
		}
		return 'grid';
	}

	/**
	 * Enqueue Elementor loop template CSS once per request.
	 *
	 * @param int $template_id Loop template post ID.
	 */
	private function enqueue_loop_template_css( $template_id ) {
		static $done = array();
		$template_id = (int) $template_id;
		if ( isset( $done[ $template_id ] ) ) {
			return;
		}
		$done[ $template_id ] = true;

		if ( class_exists( '\ElementorPro\Modules\LoopBuilder\Files\Css\Loop' ) ) {
			\ElementorPro\Modules\LoopBuilder\Files\Css\Loop::create( $template_id )->enqueue();
			return;
		}

		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			\Elementor\Core\Files\CSS\Post::create( $template_id )->enqueue();
		}
	}

	/**
	 * Print per-item dynamic CSS for a loop item.
	 *
	 * @param int $product_id  Product post ID.
	 * @param int $template_id Loop template post ID.
	 */
	private function print_loop_dynamic_css( $product_id, $template_id ) {
		if ( ! class_exists( '\ElementorPro\Modules\LoopBuilder\Files\Css\Loop_Dynamic_CSS' ) ) {
			return;
		}

		$document = \Elementor\Plugin::$instance->documents->get_doc_for_frontend( $template_id );
		if ( ! $document ) {
			return;
		}

		\Elementor\Plugin::$instance->documents->switch_to_document( $document );
		$css_file = \ElementorPro\Modules\LoopBuilder\Files\Css\Loop_Dynamic_CSS::create( $product_id, $template_id );
		$post_css = $css_file->get_content();
		if ( ! empty( $post_css ) ) {
			$css = str_replace( '.elementor-' . $product_id, '.e-loop-item-' . $product_id, $post_css );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<style id="loop-dynamic-' . esc_attr( (string) $product_id ) . '">' . $css . '</style>';
		}
		\Elementor\Plugin::$instance->documents->restore_document();
	}

	/**
	 * Minimal fallback card when Elementor template 3608 is unavailable.
	 *
	 * @param WC_Product $product Product.
	 * @param string     $context Render context.
	 * @return string
	 */
	private function render_fallback_product_card( WC_Product $product, $context = '' ) {
		$pid     = $product->get_id();
		$url     = get_permalink( $pid );
		$name    = $product->get_name();
		$price   = function_exists( 'biopentra_loop_card_format_grid_price_html' )
			? biopentra_loop_card_format_grid_price_html( $product )
			: $product->get_price_html();
		$payload = function_exists( 'biopentra_loop_card_build_payload' )
			? biopentra_loop_card_build_payload( $product )
			: array();

		$attrs = 'class="biopentra-loop-card-root biopentra-loop-card-root--fallback"';
		if ( $context !== '' ) {
			$attrs .= ' data-biopentra-render-context="' . esc_attr( $context ) . '"';
		}

		$rating = function_exists( 'biopentra_loop_card_get_rating_html' )
			? biopentra_loop_card_get_rating_html( $product )
			: '';

		ob_start();
		?>
		<div <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-biopentra-product="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
			<a class="biopentra-loop-card-title-link biopentra-loop-card-stretch" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a>
			<?php
			if ( $rating !== '' ) {
				echo $rating; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from WC helpers + escaped count/avg wrappers.
			}
			?>
			<div class="biopentra-loop-card__price"><?php echo wp_kses_post( $price ); ?></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

/**
 * Default Elementor loop item template post ID.
 *
 * @return int
 */
function biopentra_loop_card_get_template_post_id() {
	return Biopentra_Loop_Card_Product_Card_Renderer::instance()->get_template_post_id();
}

/**
 * Whether Elementor loop rendering is available.
 *
 * @param int|null $template_id Template post ID.
 * @return bool
 */
function biopentra_loop_card_can_render_elementor_loop( $template_id = null ) {
	return Biopentra_Loop_Card_Product_Card_Renderer::instance()->can_render_elementor_loop( $template_id );
}

/**
 * Render one canonical product card.
 *
 * @param WC_Product $product Product.
 * @param array      $args    Optional: context, layout, wrapper_class, template_id.
 * @return string HTML.
 */
function biopentra_loop_card_render_product_card( WC_Product $product, array $args = array() ) {
	return Biopentra_Loop_Card_Product_Card_Renderer::instance()->render_product_card( $product, $args );
}
