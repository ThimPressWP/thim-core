<?php
namespace ThimPress\Customizer\Field;

use ThimPress\Customizer\Modules\Field;

class Slider extends Field {

	public $type = 'thim-slider';

	protected $control_class = '\ThimPress\Customizer\Control\Slider';

	protected $control_has_js_template = true;

	public function filter_setting_args( $args, $wp_customize ) {
		if ( $args['id'] === $this->args['id'] ) {
			$args = parent::filter_setting_args( $args, $wp_customize );

			// Set the sanitize_callback if none is defined.
			if ( ! isset( $args['sanitize_callback'] ) || ! $args['sanitize_callback'] ) {
				$args['sanitize_callback'] = function ( $value ) {
					// Responsive value: { desktop, tablet, mobile }
					if ( is_array( $value ) ) {
						$sanitized = array();
						foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
							$sanitized[ $device ] = isset( $value[ $device ] ) && '' !== $value[ $device ]
								? filter_var( $value[ $device ], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION )
								: '';
						}
						return $sanitized;
					}
					// Scalar (non-responsive or legacy).
					return filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
				};
			}
		}

		return $args;
	}

	public function filter_control_args( $args, $wp_customize ) {
		if ( $args['id'] === $this->args['id'] ) {
			$args         = parent::filter_control_args( $args, $wp_customize );
			$args['type'] = 'thim-slider';
		}

		return $args;
	}
}
