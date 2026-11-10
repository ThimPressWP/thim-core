<?php
namespace ThimPress\Customizer\Field;

use ThimPress\Customizer\Modules\Field;

// phpcs:ignore WordPress.NamingConventions.ValidClassName.NotSnakeCaseDash
class Thim_Builder extends Field {

	public $type = 'thim-builder';

	protected $control_class = '\ThimPress\Customizer\Control\LayoutBuilder';

	public function filter_setting_args( $args, $wp_customize ) {
		if ( $args['id'] === $this->args['id'] ) {
			$args = parent::filter_setting_args( $args, $wp_customize );

			if ( ! isset( $args['sanitize_callback'] ) || ! $args['sanitize_callback'] ) {
				$args['sanitize_callback'] = array( $this, 'sanitize' );
			}
		}

		return $args;
	}

	public function filter_control_args( $args, $wp_customize ) {
		if ( $args['id'] === $this->args['id'] ) {
			$args         = parent::filter_control_args( $args, $wp_customize );
			$args['type'] = 'thim-builder';

			foreach ( array( 'choices', 'input_attrs', 'default' ) as $key ) {
				if ( isset( $this->args[ $key ] ) ) {
					$args[ $key ] = $this->args[ $key ];
				}
			}
		}

		return $args;
	}

	/**
	 * Sanitize layout data.
	 * Structure: { device: { row: { zone: ['item-id', ...] } } }
	 *
	 * @param mixed $value
	 * @return array
	 */
	public function sanitize( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $value as $device_key => $device_data ) {
			if ( ! is_array( $device_data ) ) {
				continue;
			}

			$device_key = sanitize_key( $device_key );

			$sanitized[ $device_key ] = array();

			foreach ( $device_data as $row_key => $row_data ) {
				if ( ! is_array( $row_data ) ) {
					continue;
				}

				$row_key = sanitize_key( $row_key );
				$sanitized[ $device_key ][ $row_key ] = array();

				foreach ( $row_data as $zone_key => $zone_items ) {
					if ( ! is_array( $zone_items ) ) {
						continue;
					}

					$sanitized[ $device_key ][ $row_key ][ sanitize_key( $zone_key ) ] = array_map( 'sanitize_key', $zone_items );
				}
			}
		}

		return $sanitized;
	}
}
