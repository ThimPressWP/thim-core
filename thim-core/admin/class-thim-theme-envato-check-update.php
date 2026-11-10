<?php

/**
 * Theme update checker.
 *
 * Used to be backed by Envato's market API with a hardcoded seller token.
 * Now it asks our own market server (`/license/version`) using the
 * site_code we got when the user activated the license, so update checks
 * no longer leave the consolidated site.
 *
 * The class name is kept for backwards compatibility with callers across
 * the dashboard — only the internals changed.
 *
 * @since 1.1.0
 */
class Thim_Theme_Envato_Check_Update {

	/**
	 * Activation `site_code` saved at license activation time.
	 *
	 * @var string
	 */
	private $site_code;

	/**
	 * Theme slug (Envato slug stored locally) — matches `_slug` on the
	 * server's plugin/theme post.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Local theme version.
	 *
	 * @var string
	 */
	private $local_version;

	/**
	 * Envato item id — only used for the legacy site_key fallback check.
	 *
	 * @var string
	 */
	private $envato_item_id = '';

	/**
	 * Cached server-side data so we hit the network only once per request.
	 *
	 * @var array|false|null
	 */
	private $theme_data = null;

	/**
	 * License expiry state reported by the server (thimpress purchases).
	 *
	 * @var bool
	 */
	private $expired = false;

	/**
	 * The true latest version reported by the server even when the license has
	 * expired — used to prompt a renewal.
	 *
	 * @var string
	 */
	private $server_latest = '';

	/**
	 * License expiry date (thimpress purchases) as returned by the server.
	 *
	 * @var string
	 */
	private $server_expire = '';

	public function __construct( $slug, $local_version, $site_code = '', $envato_item_id = '' ) {
		$this->slug           = (string) $slug;
		$this->local_version  = (string) $local_version;
		$this->site_code      = (string) ( $site_code ?: Thim_Product_Registration::get_data_theme_register( 'purchase_token' ) );
		$this->envato_item_id = (string) $envato_item_id;
	}

	public function can_update() {
		$remote_version = $this->get_remote_version();
		if ( ! $remote_version ) {
			return false;
		}

		return version_compare( $remote_version, $this->local_version, '>' );
	}

	public function get_remote_version() {
		$data = $this->get_theme_data();
		return $data['version'] ?? false;
	}

	/**
	 * Whether the server reported the thimpress license as expired.
	 */
	public function is_expired() {
		$this->get_theme_data();
		return $this->expired;
	}

	/**
	 * The true latest version the server reported (even when expired).
	 */
	public function get_server_latest() {
		$this->get_theme_data();
		return $this->server_latest;
	}

	/**
	 * The license expiry date the server reported ('' when not applicable).
	 */
	public function get_server_expire() {
		$this->get_theme_data();
		return $this->server_expire;
	}

	/**
	 * Return a stable shape compatible with the dashboard panel that
	 * stores update info in `thim_core_check_update_themes`. Server only
	 * gives back the latest version; richer fields fall back to local
	 * WP_Theme data so the UI keeps working.
	 *
	 * @return array|false
	 */
	public function get_theme_data() {
		if ( null !== $this->theme_data ) {
			return $this->theme_data;
		}

		$version = $this->fetch_remote_version();

		if ( ! $version ) {
			$this->theme_data = false;
			return false;
		}

		// Try matching the slug to a local theme folder first; fall back to
		// the active theme so renamed folders or child themes still surface
		// the parent's metadata in the update panel.
		$wp_theme = null;
		if ( function_exists( 'wp_get_theme' ) ) {
			$wp_theme = wp_get_theme( $this->slug );
			if ( ! $wp_theme || is_wp_error( $wp_theme ) || ! $wp_theme->exists() ) {
				$wp_theme = wp_get_theme();
			}
		}
		$is_theme = $wp_theme && ! is_wp_error( $wp_theme ) && $wp_theme->exists();

		$this->theme_data = array(
			'theme_name'   => $is_theme ? $wp_theme->get( 'Name' ) : $this->slug,
			'description'  => $is_theme ? $wp_theme->get( 'Description' ) : '',
			'version'      => $version,
			'icon'         => '',
			'author_name'  => $is_theme ? $wp_theme->get( 'Author' ) : '',
			'author_url'   => $is_theme ? $wp_theme->get( 'AuthorURI' ) : '',
			'rating'       => 0,
			'rating_count' => 0,
			'url'          => $is_theme ? $wp_theme->get( 'ThemeURI' ) : '',
		);

		return $this->theme_data;
	}

	/**
	 * Hit `/license/version?site_code=…&slug=…` on the market server.
	 */
	private function fetch_remote_version() {
		if ( '' === $this->slug ) {
			return false;
		}

		// New flow: activation carries a site_code → /license/version.
		if ( '' !== $this->site_code ) {
			$version = $this->fetch_version_by_site_code();
			if ( $version ) {
				return $version;
			}

			// Expired thimpress license: block updates; don't fall through to
			// the legacy site_key endpoint.
			if ( $this->expired ) {
				return false;
			}
		}

		// Legacy flow: customer registered through Envato has only a site_key.
		return $this->fetch_version_legacy();
	}

	/**
	 * Hit `/license/version?site_code=…&slug=…` on the market server.
	 */
	private function fetch_version_by_site_code() {
		$url = add_query_arg(
			array(
				'site_code' => $this->site_code,
				'slug'      => $this->slug,
			),
			Thim_Admin_Config::get( 'api_thim_market' ) . '/license/version'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body ) || ( $body['status'] ?? '' ) !== 'success' ) {
			return false;
		}

		$data = isset( $body['data'] ) && is_array( $body['data'] ) ? $body['data'] : array();

		$this->server_latest = isset( $data['latest_version'] ) ? (string) $data['latest_version'] : '';
		$this->server_expire = isset( $data['expire'] ) ? (string) $data['expire'] : '';
		$this->expired       = ! empty( $data['expired'] );

		// Even when expired we still return the latest version so the dashboard
		// can show "new version available" — the update action itself is what's
		// gated (client shows a Renew button, server blocks the download).
		$version = $data['version'] ?? false;

		return $version ? (string) $version : false;
	}

	/**
	 * Legacy version check for customers registered through Envato who only
	 * have a site_key (no site_code). The still-live `theme-update` endpoint
	 * returns the latest version for the Envato item id.
	 */
	private function fetch_version_legacy() {
		$site_key = Thim_Product_Registration::get_site_key();
		if ( empty( $site_key ) || 'site_key' === $site_key || '' === $this->envato_item_id ) {
			return false;
		}

		$url = add_query_arg(
			array( 'item-id' => $this->envato_item_id ),
			Thim_Admin_Config::get( 'host_envato_app' ) . '/theme-update/'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Server responds with wp_send_json_success( $version ): { success: true, data: "x.y.z" }.
		if ( empty( $body ) || empty( $body['success'] ) ) {
			return false;
		}

		$version = $body['data'] ?? false;

		return $version ? (string) $version : false;
	}
}
