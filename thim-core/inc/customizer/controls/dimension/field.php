<?php
namespace ThimPress\Customizer\Field;

use ThimPress\Customizer\Modules\Field;

class Dimension extends Field {

	public $type = 'thim-dimension';

	protected $control_class = '\ThimPress\Customizer\Control\Dimension';

	protected $control_has_js_template = true;

	public function filter_setting_args( $args, $wp_customize ) {
		if ( $args['id'] === $this->args['id'] ) {
			$args = parent::filter_setting_args( $args, $wp_customize );

			if ( ! isset( $args['sanitize_callback'] ) || ! $args['sanitize_callback'] ) {
				$args['sanitize_callback'] = function ( $value ) {
					// Responsive value: { desktop, tablet, mobile }.
					if ( is_array( $value ) ) {
						$sanitized = array();
						foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
							$sanitized[ $device ] = isset( $value[ $device ] ) && '' !== $value[ $device ]
								? sanitize_text_field( $value[ $device ] )
								: '';
						}
						return $sanitized;
					}
					// Scalar (non-responsive or legacy).
					return sanitize_text_field( $value );
				};
			}
		}

		return $args;
	}

	public function filter_control_args( $args, $wp_customize ) {
		if ( $args['id'] === $this->args['id'] ) {
			$args         = parent::filter_control_args( $args, $wp_customize );
			$args['type'] = 'thim-dimension';
		}

		return $args;
	}
}
