<?php
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Thim_Core_Toc' ) ) {
	class Thim_Core_Toc {
		/** @var Thim_Core_Toc|null */
		private static $_instance = null;

		public static function instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		private function __construct() {
			add_action( 'init', array( $this, 'register_shortcodes' ) );
		}

		public function register_shortcodes() {
			add_shortcode( 'thim_core_toc', array( $this, 'render_toc_shortcode' ) );
		}

		public function render_toc_shortcode( $atts ) {
			global $post;

			$atts = shortcode_atts(
				array(
					'include' => 'h2,h3,h4,h5,h6',
					'title'   => '',
				),
				$atts,
				'thim_core_toc'
			);

			if ( ! is_singular() || empty( $post->post_content ) ) {
				return '';
			}

			$content = $post->post_content;

			$allowed_headings = array_map( 'trim', explode( ',', $atts['include'] ) );
			$allowed_headings = array_filter(
				$allowed_headings,
				function ( $tag ) {
					return preg_match( '/^h[2-6]$/', $tag );
				}
			);

			if ( empty( $allowed_headings ) ) {
				return '';
			}

			$pattern = '/<(' . implode( '|', $allowed_headings ) . ')([^>]*)>(.*?)<\/\1>/';
			preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

			if ( empty( $matches ) ) {
				return esc_html( 'No headings found.', 'thim-core' );
			}

			// Enqueue assets (only when shortcode is present)
			wp_enqueue_style( 'thim-core-toc', THIM_CORE_ASSETS_URI . '/css/toc.css', array(), THIM_CORE_VERSION );
			wp_enqueue_script( 'thim-core-toc', THIM_CORE_ASSETS_URI . '/js/toc.js', array( 'jquery' ), THIM_CORE_VERSION, true );
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) { // load in Elementor editor preview
				printf(
					'<link rel="stylesheet" href="%s/css/toc.css?ver=%s">',
					esc_url( THIM_CORE_ASSETS_URI ),
					esc_attr( THIM_CORE_VERSION )
				);
			}

			$toc_icon = '<svg viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg" fill="none">'
				. '<rect x="1" y="1" width="22" height="22" rx="3" ry="3" stroke="currentColor" stroke-width="1.5" fill="transparent"/>'
				. '<text x="4.5" y="8" font-size="5" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="currentColor">1</text>'
				. '<text x="4.5" y="13.5" font-size="5" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="currentColor">2</text>'
				. '<text x="4.5" y="19" font-size="5" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="currentColor">3</text>'
				. '<line x1="9" y1="7" x2="19" y2="7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
				. '<line x1="9" y1="12.5" x2="19" y2="12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
				. '<line x1="9" y1="18" x2="19" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
				. '</svg>';

			$toc  = '<div class="thim-core-toc-wrapper">';
			$toc .= '<div class="thim-core-toc-toggle">' . $toc_icon . '</div>';

			$toc .= '<div class="thim-core-toc">';
			if ( ! empty( $atts['title'] ) ) {
				$toc .= '<h4>' . wp_kses_post( $atts['title'] ) . '</h4>';
			}

				$toc .= '<ul>';
			foreach ( $matches as $match ) {
				$level = isset( $match[1] ) ? $match[1] : 'h2';
				$text  = isset( $match[3] ) ? strip_tags( $match[3] ) : '';
				$slug  = sanitize_title( $text );

				$toc .= '<li class="toc-level-' . esc_attr( $level ) . '"><a href="#' . esc_attr( $slug ) . '">' . esc_html( $text ) . '</a></li>';
			}
				$toc .= '</ul>';
				$toc .= '</div>';

			$toc .= '</div>';

			return $toc;
		}
	}

	Thim_Core_Toc::instance();
}
