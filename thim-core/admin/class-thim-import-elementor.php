<?php
/**
 * Class Thim_Import_Elementor
 * Admin subpage for importing Elementor pages
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Thim_Import_Elementor extends Thim_Admin_Sub_Page {
	/**
	 * Page key for dashboard.
	 *
	 * @since 2.4.5
	 * @var string
	 */
	public $key_page = 'import-elementor';

	/**
	 * constructor.
	 */
	protected function __construct() {
		parent::__construct();

		$this->hooks();
	}

	/**
	 * Check whether remote templates are available via the kit REST endpoint.
	 *
	 * @return bool
	 */
	private function has_remote_templates() {
		$theme_domain = get_template();
		$cache_key    = 'thim_core_has_remote_templates_' . $theme_domain;
		$cached       = get_transient( $cache_key );

		if ( false !== $cached ) {
			return (bool) $cached;
		}

		// Dispatch the internal REST request so permission callbacks run in this admin context.
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			return false;
		}

		try {
			$request  = new WP_REST_Request( 'GET', '/thim-ekit/get-templates' );
			$response = rest_do_request( $request );
			if ( is_wp_error( $response ) ) {
				return false;
			}

			$status = method_exists( $response, 'get_status' ) ? $response->get_status() : 200;
			if ( intval( $status ) !== 200 ) {
				return false;
			}

			$data = method_exists( $response, 'get_data' ) ? $response->get_data() : null;
			if ( ! is_array( $data ) ) {
				return false;
			}

			$pages_free  = isset( $data['free']['page'] ) && is_array( $data['free']['page'] ) ? $data['free']['page'] : array();
			$pages_theme = isset( $data['theme']['page'] ) && is_array( $data['theme']['page'] ) ? $data['theme']['page'] : array();

			$has_templates = ( count( $pages_free ) > 0 || count( $pages_theme ) > 0 );

			set_transient( $cache_key, $has_templates ? 1 : 0, DAY_IN_SECONDS );

			return $has_templates;
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Register hooks for the sub page.
	 */
	private function hooks() {
		add_filter( 'thim_dashboard_sub_pages', array( $this, 'add_sub_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// AJAX handler for creating new Elementor pages
		add_action( 'wp_ajax_thim_core_create_elementor_library', array( $this, 'ajax_create_elementor_library' ) );
	}

	/**
	 * Add this admin sub page
	 *
	 * @param array $sub_pages
	 * @return array
	 */
	public function add_sub_page( $sub_pages ) {
		if ( ! current_user_can( 'administrator' ) ) {
			return $sub_pages;
		}

		// Only add if there are remote templates available
		if ( ! $this->has_remote_templates() ) {
			return $sub_pages;
		}

		$sub_pages[ $this->key_page ] = array(
			'title' => __( 'Elementor Pages', 'thim-core' ),
			'icon'  => '<svg width="25px" height="25px" viewBox="0 0 25 25" version="1.1" xmlns="http://www.w3.org/2000/svg">
						<path d="M0,0 L0,25 L25,25 L25,0 L0,0 Z M2.27272727,2.27272727 L22.7272727,2.27272727 L22.7272727,22.7272727 L2.27272727,22.7272727 L2.27272727,2.27272727 Z M6.81818182,6.81818182 L6.81818182,18.1818182 L9.09090909,18.1818182 L9.09090909,6.81818182 L6.81818182,6.81818182 Z M11.3636364,6.81818182 L11.3636364,9.09090909 L18.1818182,9.09090909 L18.1818182,6.81818182 L11.3636364,6.81818182 Z M11.3636364,11.3636364 L11.3636364,13.6363636 L18.1818182,13.6363636 L18.1818182,11.3636364 L11.3636364,11.3636364 Z M11.3636364,15.9090909 L11.3636364,18.1818182 L18.1818182,18.1818182 L18.1818182,15.9090909 L11.3636364,15.9090909 Z"></path>
					</svg>',
		);

		return $sub_pages;
	}

	public function enqueue_scripts() {
		// Only load run for this subpage
		if ( ! $this->is_myself() ) {
			return;
		}

		// Admin dashboard helper script to show library UI on the Elements subpage
		wp_register_script( 'thim-core-import-elementor', THIM_CORE_ADMIN_URI . '/assets/js/import-elementor.js', array( 'wp-api-fetch', 'jquery' ), THIM_CORE_VERSION, true );
		$theme_obj        = wp_get_theme();
		$theme_textdomain = '';
		if ( is_child_theme() ) {
			$parent           = wp_get_theme( $theme_obj->parent()->template );
			$theme_textdomain = $parent->get( 'TextDomain' );
		} else {
			$theme_textdomain = $theme_obj->get( 'TextDomain' );
		}

		wp_localize_script(
			'thim-core-import-elementor',
			'ThimImportElementor',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'security'     => wp_create_nonce( 'thim_import_elementor' ),
				'theme'        => $theme_textdomain ?: 'thim-kit-free',
				'siteUrl'      => get_site_url(),
				'adminUrl'     => get_admin_url(),
				'el_v4_status' => get_option( 'elementor_experiment-e_atomic_elements', 'active' ),
			)
		);
		wp_enqueue_script( 'thim-core-import-elementor' );

		// Admin CSS
		wp_register_style( 'thim-core-import-elementor', THIM_CORE_ADMIN_URI . '/assets/css/import-elementor.css', array(), THIM_CORE_VERSION );
		wp_enqueue_style( 'thim-core-import-elementor' );
	}

	/**
	 * AJAX handler for admin-ajax.php to create an elementor_library post.
	 */
	public function ajax_create_elementor_library() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		check_ajax_referer( 'thim_import_elementor', 'security' );

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : __( 'Imported Layout', 'thim-core' );

		// Allow admin UI to request a different post type (page or elementor_library)
		$post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'elementor_library';
		$allowed   = array( 'elementor_library', 'page' );
		if ( ! in_array( $post_type, $allowed, true ) ) {
			$post_type = 'elementor_library';
		}

		$postarr = array(
			'post_title'  => wp_strip_all_tags( $title ),
			'post_status' => 'draft', // Create as draft, admin can publish after review
			'post_type'   => $post_type,
		);

		$post_id = wp_insert_post( $postarr );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create template post.', 'thim-core' ) ) );
		}

		// Set Elementor specific meta for full width template and builder mode
		if ( 'page' === $post_type ) {
			update_post_meta( $post_id, '_wp_page_template', 'elementor_header_footer' );
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		}

		wp_send_json_success( array( 'post_id' => $post_id ) );
	}
}
