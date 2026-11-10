<?php

/**
 * Class Thim_Cookie_Consent.
 *
 * @since 2.3.6
 */
class Thim_Cookie_Consent extends Thim_Admin_Sub_Page {
	/**
	 * @var string
	 *
	 * @since 2.3.6
	 */
	public $key_page = 'cookie-consent';

	/**
	 * Thim_Cookie_Consent constructor.
	 *
	 * @since 2.3.6
	 */
	protected function __construct() {
		parent::__construct();

		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 2.3.6
	 */
	private function hooks() {
		add_filter( 'thim_dashboard_sub_pages', array( $this, 'add_sub_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'fe_enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'render_banner_inline' ) );

		/* Register AJAX hooks */
		add_action( 'wp_ajax_update_cookie_manager_form', array( $this, 'update_cookie_manager_form' ) );
		add_action( 'wp_ajax_cookie_consent_settings', array( $this, 'thim_save_cookie_consent_settings' ) );
		add_action( 'wp_ajax_thim_edit_cookie_list', array( $this, 'thim_edit_cookie_list_handler' ) );
	}

	/**
	 * Add sub page.
	 *
	 * @since 2.3.6
	 *
	 *
	 * @param $sub_pages
	 *
	 * @return mixed
	 */
	public function add_sub_page( $sub_pages ) {

		if ( ! current_user_can( 'administrator' ) ) {
			return $sub_pages;
		}

		$sub_pages['cookie-consent'] = array(
			'title' => __( 'Cookie Consent', 'thim-core' ),
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="25" height="26" fill="none" stroke="#50575E" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M12 2a10 10 0 0 0-1 19.95 10 10 0 0 0 9-14.82A2.5 2.5 0 0 1 15.5 5a2.5 2.5 0 0 1-2.45-3A10 10 0 0 0 12 2Z"/>
                        <circle cx="10.5" cy="9" r="1.5"/>
                        <circle cx="15.5" cy="13" r="1.5"/>
                        <circle cx="7.5" cy="16" r="1.5"/>
                    </svg>',
		);

		return $sub_pages;
	}

	/**
	 * Get default cookie categories.
	 *
	 * @since 2.3.6
	 *
	 * @return array
	 */
	public static function get_default_categories() {
		return array(
			'necessary'  => array(
				'title' => 'Necessary',
				'desc'  => 'Necessary cookies are required to enable the basic features of this site, such as providing secure log-in or adjusting your consent preferences. These cookies do not store any personally identifiable data.',
			),
			'analytics'  => array(
				'title' => 'Analytics',
				'desc'  => 'Analytical cookies are used to understand how visitors interact with the website. These cookies help provide information on metrics such as the number of visitors, bounce rate, traffic source, etc.',
			),
			'ads'        => array(
				'title' => 'Advertising',
				'desc'  => 'Advertisement cookies are used to provide visitors with customised advertisements based on the pages you visited previously and to analyse the effectiveness of the ad campaigns.',
			),
			'functional' => array(
				'title' => 'Functional',
				'desc'  => 'Functional cookies help perform certain functionalities like sharing the content of the website on social media platforms, collecting feedback, and other third-party features.',
			),
		);
	}

	/**
	 * Get cookie list for client browser only.
	 *
	 * @since 2.3.6
	 *
	 * @return array
	 */
	public static function cookie_details() {
		$cookies = $_COOKIE;

		// Define a list of admin-related cookies to exclude
		$excluded_cookies = array(
			'wp_',
			'wordpress_',
			'wp-settings-',
			'pmpro',
			'litespeed_',
			'_lscache',
			'PHPSESSID',
			'tk_ai',
		);

		$cookie_details = array();
		foreach ( $cookies as $name => $value ) {
			// Skip excluded cookies
			foreach ( $excluded_cookies as $excluded ) {
				if ( strpos( $name, $excluded ) === 0 ) {
					continue 2; // Skip this cookie and move to the next one
				}
			}

			// Add cookie details to the array
			$cookie_details[] = array(
				'name'   => htmlspecialchars( $name ),
				'value'  => htmlspecialchars( $value ),
				'domain' => $_SERVER['HTTP_HOST'],
				'type'   => self::get_cookie_type( $name ),
			);
		}

		return $cookie_details;
	}

	/**
	 * Get cookie type
	 *
	 * @since 2.3.6
	 *
	 * @param string $cookie_name
	 * @param string|null $cookie_domain
	 *
	 * @return string
	 */
	private static function get_cookie_type( $cookie_name, $cookie_domain = null ) {
		$current_domain = $_SERVER['HTTP_HOST'];

		// Popular cookies mapping
		$popular_cookies = array(
			'google_analytics' => array(
				'names' => array( '_ga', '_gid', '_gat' ),
				'type'  => 'Third-party',
			),
			'facebook'         => array(
				'names' => array( 'fr', '_fbp', 'datr' ),
				'type'  => 'Third-party',
			),
		);

		// Check if the cookie matches any popular cookie
		foreach ( $popular_cookies as $service => $data ) {
			foreach ( $data['names'] as $popular_name ) {
				if ( strpos( $cookie_name, $popular_name ) === 0 ) {
					return esc_html__( $data['type'], 'thim-core' );
				}
			}
		}

		// Determine cookie type based on domain
		if ( $cookie_domain ) {
			if ( $cookie_domain === $current_domain ) {
				return esc_html__( 'First-party', 'thim-core' );
			} elseif ( strpos( $cookie_domain, $current_domain ) !== false ) {
				return esc_html__( 'Second-party', 'thim-core' );
			} else {
				return esc_html__( 'Third-party', 'thim-core' );
			}
		}

		// Default to First-party if no domain is provided
		return esc_html__( 'First-party', 'thim-core' );
	}

	/**
	 * Handle AJAX request to update cookie manager form.
	 *
	 * @since 2.3.6
	 */
	public function update_cookie_manager_form() {
		// Verify the AJAX request
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cookie_consent_settings_nonce' ) ) {
			wp_send_json_error( array( 'message' => array( __( 'Invalid nonce.', 'thim-core' ) ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'thim-core' ) ), 403 );
		}

		if ( ! isset( $_POST['cookie_category'] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request.' ) );
			return;
		}

		// Sanitize the input
		$cookie_category = sanitize_text_field( $_POST['cookie_category'] );

		// Retrieve the category data
		$categories = self::get_data_settings()['cookie_categories'];

		// Check if the category exists
		if ( isset( $categories[ $cookie_category ] ) ) {
			$category_data = $categories[ $cookie_category ];

			ob_start();

			$data = array(
				'cookie_category' => $cookie_category,
				'categories'      => $categories,
				'cookie_list'     => self::get_data_settings()['cookie_list'],
			);
			Thim_Template_Helper::template( 'cookie-category-fields.php', $data, true );

			$form_html = ob_get_clean();

			wp_send_json_success( array( 'form_html' => $form_html ) );
		} else {
			wp_send_json_error( array( 'message' => 'Category not found.' ) );
		}
	}

	/**
	 * Save cookie_consent_settings
	 *
	 * @since 2.3.6
	 *
	 */
	public function thim_save_cookie_consent_settings() {
		// Verify nonce
		if ( ! isset( $_POST['cookie_consent_nonce'] ) || ! wp_verify_nonce( $_POST['cookie_consent_nonce'], 'cookie_consent_settings_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
			exit;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'thim-core' ) ), 403 );
		}

		// Hander cookie banner settings data
		$enable_popup           = isset( $_POST['enable_popup'] ) ? sanitize_text_field( $_POST['enable_popup'] ) : '';
		$popup_position         = isset( $_POST['popup_position'] ) ? sanitize_text_field( $_POST['popup_position'] ) : 'bottom-left';
		$enable_mobile_popup    = isset( $_POST['enable_mobile_popup'] ) ? sanitize_text_field( $_POST['enable_mobile_popup'] ) : '';
		$enable_revisit_button  = isset( $_POST['enable_revisit_button'] ) ? sanitize_text_field( $_POST['enable_revisit_button'] ) : '';
		$consent_message        = isset( $_POST['consent_message'] ) ? wp_kses_post( $_POST['consent_message'] ) : '';
		$customise_consent_mess = isset( $_POST['customise_consent_mess'] ) ? wp_kses_post( $_POST['customise_consent_mess'] ) : '';

		// Handle cookie categories data
		$cookie_category = isset( $_POST['cookie_category'] ) ? sanitize_text_field( $_POST['cookie_category'] ) : 'necessary';
		$cookie_title    = isset( $_POST['cat_cookie_title'] ) ? sanitize_text_field( $_POST['cat_cookie_title'] ) : '';
		$cookie_desc     = isset( $_POST['cat_cookie_desc'] ) ? sanitize_text_field( $_POST['cat_cookie_desc'] ) : '';

		// Initialize default cookie categories with default values
		$categories_data = get_option( 'thim_cookie_categories', self::get_default_categories() );

		// Update the specific category with submitted data
		if ( $cookie_category ) {
			$categories_data[ $cookie_category ] = array(
				'title' => $cookie_title ?: $categories_data[ $cookie_category ]['title'],
				'desc'  => $cookie_desc ?: $categories_data[ $cookie_category ]['desc'],
			);
		}

		// Handle cookie list data grouped by category
		$cookie_list_data = get_option( 'thim_cookie_list', array() );

		// Ensure the category exists in the cookie list data
		if ( ! isset( $cookie_list_data[ $cookie_category ] ) ) {
			$cookie_list_data[ $cookie_category ] = array();
		}

		// Append new cookie list data to the existing category
		if ( isset( $_POST['ck_list_id'] ) && is_array( $_POST['ck_list_id'] ) ) {
			foreach ( $_POST['ck_list_id'] as $index => $id ) {
				$cookie_list_data[ $cookie_category ][] = array(
					'id'       => sanitize_text_field( $id ),
					'domain'   => sanitize_text_field( $_POST['ck_list_domain'][ $index ] ?? '' ),
					'duration' => sanitize_text_field( $_POST['ck_list_duration'][ $index ] ?? '' ),
					'desc'     => sanitize_text_field( $_POST['ck_list_desc'][ $index ] ?? '' ),
					'src'      => sanitize_text_field( $_POST['ck_list_src'][ $index ] ?? '' ),
				);
			}
		}

		// Save data to wp_options table
		if ( isset( $_POST['cookie_category'] ) ) {
			update_option( 'thim_cookie_categories', $categories_data );
			update_option( 'thim_cookie_list', $cookie_list_data );
		} else {
			update_option( 'thim_cookie_enable_popup', $enable_popup );
			update_option( 'thim_cookie_popup_position', $popup_position );
			update_option( 'thim_cookie_enable_mobile_popup', $enable_mobile_popup );
			update_option( 'thim_cookie_enable_revisit_button', $enable_revisit_button );
			update_option( 'thim_cookie_consent_message', $consent_message );
			update_option( 'thim_cookie_customise_consent_mess', $customise_consent_mess );
		}

		exit;
	}


	/**
	 * Edit cookie_list in thim_cookie_list option
	 *
	 * @since 2.3.6
	 *
	 */
	public function thim_edit_cookie_list_handler() {
		// Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'delete_cookie_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'thim-core' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'thim-core' ) ), 403 );
		}

		$cookie_id       = sanitize_text_field( $_POST['cookie_id'] );
		$cookie_category = sanitize_text_field( $_POST['cookie_category'] );

		// Retrieve the existing cookie list
		$cookie_list = get_option( 'thim_cookie_list', array() );

		if ( isset( $cookie_list[ $cookie_category ] ) ) {
			// Filter out the cookie with the specified ID
			$cookie_list[ $cookie_category ] = array_filter(
				$cookie_list[ $cookie_category ],
				function ( $cookie ) use ( $cookie_id ) {
					return $cookie['id'] !== $cookie_id;
				}
			);

			// Update the option
			update_option( 'thim_cookie_list', $cookie_list );

			wp_send_json_success( array( 'message' => 'Cookie deleted successfully.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Cookie not found.' ) );
		}

		exit;
	}

	/**
	 * Get arguments for template.
	 *
	 * @since 2.3.6
	 *
	 * @return null
	 */
	protected function get_template_args() {
		$args = self::get_data_settings();

		return $args;
	}

	/**
	 * Get data cookie consent settings
	 *
	 * @since 2.3.6
	 *
	 * @return string
	 */
	public static function get_data_settings() {
		// Retrieve all cookie consent settings from wp_options
		$options = array(
			'enable_popup'           => get_option( 'thim_cookie_enable_popup', '' ),
			'popup_position'         => get_option( 'thim_cookie_popup_position', 'bottom-left' ),
			'enable_mobile_popup'    => get_option( 'thim_cookie_enable_mobile_popup', 'on' ),
			'enable_revisit_button'  => get_option( 'thim_cookie_enable_revisit_button', 'on' ),
			'consent_message'        => get_option( 'thim_cookie_consent_message', 'We use cookies to improve your experience, including essential cookies required for the website to function. By continuing, you agree to our use of cookies. <a href="#">Learn more</a>.' ),
			'customise_consent_mess' => get_option( 'thim_cookie_customise_consent_mess', '<h4 class="heading-top">Customise Consent Preferences</h4><p>We use cookies to help you navigate efficiently and perform certain functions. You will find detailed information about all cookies under each consent category below.</p><br /> {{necessary}} <br />{{analytics}}<br />{{ads}}<br />{{functional}}' ),
			'cookie_categories'      => get_option( 'thim_cookie_categories', self::get_default_categories() ),
			'cookie_list'            => get_option( 'thim_cookie_list', array() ),
		);

		return $options;
	}

	/**
	 * Render banner HTML inline into wp_footer.
	 *
	 * Both templates output hidden. JS reads document.cookie client-side
	 * to show/hide — works correctly with full-page HTML caching.
	 *
	 * @since 2.4.3
	 */
	public function render_banner_inline() {
		$options = self::get_data_settings();

		if ( $options['enable_popup'] !== 'on' ) {
			return;
		}

		$args = array(
			'id'      => 'cookie-banner',
			'options' => $options,
		);

		Thim_Template_Helper::template( 'modals/cookie-consent.php', $args, true );

		if ( $options['enable_revisit_button'] === 'on' && $options['customise_consent_mess'] ) {
			Thim_Template_Helper::template( 'cookie-revisit.php', $args, true );
		}
	}

	/**
	 * Enqueue script.
	 *
	 * @since 2.3.6
	 */
	public function enqueue_scripts() {
		if ( ! $this->is_myself() ) {
			return;
		}

		wp_enqueue_script(
			'thim-core-cookie-consent',
			THIM_CORE_ADMIN_URI . '/assets/js/cookie-consent.js',
			array(
				'jquery',
			),
			THIM_CORE_VERSION
		);

		// Pass ajaxurl and nonce to JavaScript
		wp_localize_script(
			'thim-core-cookie-consent',
			'thimCookieConsent',
			array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'cookie_consent_settings_nonce' ),
				'cookieDetails' => self::cookie_details(),
			)
		);
	}

	/**
	 * Frontend Enqueue script.
	 *
	 * @since 2.3.6
	 */
	public function fe_enqueue_scripts() {
		$options = self::get_data_settings();

		if ( $options['enable_popup'] !== 'on' ) {
			return;
		}

		wp_enqueue_style( 'thim-core-cookie-consent', THIM_CORE_URI . '/assets/css/modal.css', array(), THIM_CORE_VERSION );
		wp_enqueue_script(
			'thim-core-cookie-consent',
			THIM_CORE_URI . '/assets/js/cookie-consent.min.js',
			array( 'jquery' ),
			THIM_CORE_VERSION,
			true
		);
		wp_localize_script(
			'thim-core-cookie-consent',
			'thimCookieCfg',
			array(
				'cookie_list'           => get_option( 'thim_cookie_list', array() ),
				'enable_revisit_button' => $options['enable_revisit_button'],
			)
		);
	}
}
