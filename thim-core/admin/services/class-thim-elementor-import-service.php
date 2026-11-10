<?php
class Thim_Elementor_Import_Service {

	protected $extensions = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
		'svg'          => 'image/svg+xml',
		'gif'          => 'image/gif',
		'avif'         => 'image/avif',
	);

	private $meta_key = '_elementor_data';

	private $value = null;

	private $import_url = null;

	public function __construct( $unfiltered_value, $site_url, $meta_key = '_elementor_data' ) {
		$this->value      = $unfiltered_value;
		$this->import_url = $site_url;
		$this->meta_key   = $meta_key;
	}

	public function filter_meta() {
		add_filter( 'sanitize_post_meta_' . $this->meta_key, array( $this, 'allow_escaped_json_meta' ), 10, 3 );
	}

	public function allow_escaped_json_meta( $val, $key, $type ) {
		remove_filter( 'sanitize_post_meta_' . $this->meta_key, array( $this, 'allow_escaped_json_meta' ), 10 );

		return $this->process( $val );
	}

	public function process( $val = null ) {
		$value = ! empty( $this->value ) ? $this->value : $val;
		if ( empty( $value ) ) {
			return $val;
		}

		$this->value = $this->replace_image_urls( $value );
		$this->replace_link_urls();

		return $this->value;
	}

	public static function get_search_replace_pairs( $import_url ) {
		if ( empty( $import_url ) ) {
			return array( 'search' => array(), 'replace' => array() );
		}

		$site_url   = untrailingslashit( get_site_url() );
		$import_url = untrailingslashit( $import_url );

		if ( empty( $import_url ) || $import_url === $site_url ) {
			return array( 'search' => array(), 'replace' => array() );
		}

		// Prepare variations of host/path
		$import_host = preg_replace( '/^https?:\/\//i', '', $import_url );
		$site_host   = preg_replace( '/^https?:\/\//i', '', $site_url );

		$import_host_non_www = preg_replace( '/^www\./i', '', $import_host );
		$import_host_www     = 'www.' . $import_host_non_www;

		// Extract root domain without path if import_url has subpath (e.g. demo.thimpress.com/eduma -> demo.thimpress.com)
		$import_root_host    = explode( '/', $import_host )[0];
		$import_root_non_www = preg_replace( '/^www\./i', '', $import_root_host );
		$import_root_www     = 'www.' . $import_root_non_www;

		$hosts = array_unique( array(
			$import_host,
			$import_host_non_www,
			$import_host_www,
			$import_root_host,
			$import_root_non_www,
			$import_root_www,
		) );

		$search         = array();
		$search_slashed = array();
		$search_encoded = array();

		foreach ( $hosts as $host ) {
			$search[]         = 'https://' . $host;
			$search[]         = 'http://' . $host;
			$search[]         = '//' . $host;

			$search_slashed[] = 'https:\/\/' . str_replace( '/', '\/', $host );
			$search_slashed[] = 'http:\/\/' . str_replace( '/', '\/', $host );
			$search_slashed[] = '\/\/' . str_replace( '/', '\/', $host );

			$search_encoded[] = rawurlencode( 'https://' . $host );
			$search_encoded[] = rawurlencode( 'http://' . $host );
		}

		// Sort search arrays by length descending so longer paths match first
		usort( $search, function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );
		usort( $search_slashed, function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );
		usort( $search_encoded, function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );

		$is_ssl_site     = is_ssl() || strpos( $site_url, 'https://' ) === 0;
		$replace_slashed = ( $is_ssl_site ? 'https:\/\/' : 'http:\/\/' ) . str_replace( '/', '\/', $site_host );
		$replace_encoded = rawurlencode( $site_url );

		$all_search  = array_merge( $search, $search_slashed, $search_encoded );
		$all_replace = array_merge(
			array_fill( 0, count( $search ), $site_url ),
			array_fill( 0, count( $search_slashed ), $replace_slashed ),
			array_fill( 0, count( $search_encoded ), $replace_encoded )
		);

		return array(
			'search'  => $all_search,
			'replace' => $all_replace,
		);
	}

	public static function remap_url( $url, $import_url = '' ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return $url;
		}

		$pairs = self::get_search_replace_pairs( $import_url );
		if ( empty( $pairs['search'] ) ) {
			return $url;
		}

		return str_replace( $pairs['search'], $pairs['replace'], $url );
	}

	private function replace_link_urls() {
		if ( empty( $this->import_url ) ) {
			return;
		}

		$pairs = self::get_search_replace_pairs( $this->import_url );
		if ( empty( $pairs['search'] ) ) {
			return;
		}

		$all_search  = $pairs['search'];
		$all_replace = $pairs['replace'];

		$decoded_meta = json_decode( $this->value, true );
		if ( ! is_array( $decoded_meta ) && is_string( $this->value ) ) {
			$unslashed    = wp_unslash( $this->value );
			$decoded_meta = json_decode( $unslashed, true );
		}

		if ( is_array( $decoded_meta ) ) {
			array_walk_recursive(
				$decoded_meta,
				function ( &$value, $key ) use ( $all_search, $all_replace ) {
					if ( is_string( $value ) && ! empty( $value ) ) {
						$value = str_replace( $all_search, $all_replace, $value );
					}
				}
			);

			$this->value = wp_json_encode( $decoded_meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		// Also run replacement on the final JSON / string value
		if ( is_string( $this->value ) ) {
			$this->value = str_replace( $all_search, $all_replace, $this->value );
		}
	}

	public function replace_image_urls( $markup ) {
		// Get all slashed and un-slashed urls.
		$old_urls = $this->get_urls_to_replace( $markup );
		if ( ! is_array( $old_urls ) || empty( $old_urls ) ) {
			return $markup;
		}

		$search  = array();
		$replace = array();

		foreach ( $old_urls as $old_url ) {
			$unslashed_old = wp_unslash( $old_url );
			$new_url       = $this->remap_host( $unslashed_old );
			if ( $new_url && $new_url !== $unslashed_old ) {
				$search[]  = $unslashed_old;
				$replace[] = $new_url;

				$search[]  = str_replace( '/', '\/', $unslashed_old );
				$replace[] = str_replace( '/', '\/', $new_url );
			}
		}

		if ( ! empty( $search ) ) {
			$markup = str_replace( $search, $replace, $markup );
		}

		return $markup;
	}

	private function get_urls_to_replace( $markup ) {
		$regex = '/(?:http(?:s?):)(?:[\/\\\\\\\\|.|\w|\s|-])*\.(?:' . implode( '|', array_keys( $this->extensions ) ) . ')/m';

		if ( ! is_string( $markup ) ) {
			return array();
		}

		preg_match_all( $regex, $markup, $urls );

		$urls = array_map(
			function ( $value ) {
				return rtrim( html_entity_decode( $value ), '\\' );
			},
			$urls[0]
		);

		$urls = array_unique( $urls );

		return array_values( $urls );
	}

	private function remap_host( $url ) {
		if ( ! strpos( $url, '/uploads/' ) ) {
			return $url;
		}
		$old_url   = $url;
		$url_parts = parse_url( $url );

		if ( ! isset( $url_parts['host'] ) ) {
			return $url;
		}
		$url_parts['path'] = preg_split( '/\//', $url_parts['path'] );
		$url_parts['path'] = array_slice( $url_parts['path'], - 3 );

		$uploads_dir = wp_get_upload_dir();
		$uploads_url = $uploads_dir['baseurl'];

		$new_url = esc_url( $uploads_url . '/' . join( '/', $url_parts['path'] ) );

		return str_replace( $old_url, $new_url, $url );
	}
}
