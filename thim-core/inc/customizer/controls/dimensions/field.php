<?php
namespace ThimPress\Customizer\Field;

use ThimPress\Customizer\Modules\Field;

class Dimensions extends Field {

	public $type = 'thim-dimensions';

	protected $control_class = '\ThimPress\Customizer\Control\Dimensions';

	protected $control_has_js_template = true;

	/**
	 * No sub-fields. Everything is rendered by the single React control.
	 */
	public function init( $args = array() ) {}

	/**
	 * Filter setting args — add sanitize callback.
	 */
	public function filter_setting_args( $args, $wp_customize ) {
		if ( $args['id'] !== $this->args['id'] ) {
			return $args;
		}

		if ( ! isset( $args['sanitize_callback'] ) || ! $args['sanitize_callback'] ) {
			$args['sanitize_callback'] = array( __CLASS__, 'sanitize' );
		}

		return $args;
	}

	/**
	 * Filter control args — build the choices payload for React.
	 */
	public function filter_control_args( $args, $wp_customize ) {
		if ( $args['id'] !== $this->args['id'] ) {
			return $args;
		}

		$args = parent::filter_control_args( $args, $wp_customize );

		$labels = array(
			'left-top'       => 'Left Top',
			'left-center'    => 'Left Center',
			'left-bottom'    => 'Left Bottom',
			'right-top'      => 'Right Top',
			'right-center'   => 'Right Center',
			'right-bottom'   => 'Right Bottom',
			'center-top'     => 'Center Top',
			'center-center'  => 'Center Center',
			'center-bottom'  => 'Center Bottom',
			'font-size'      => 'Font Size',
			'font-weight'    => 'Font Weight',
			'line-height'    => 'Line Height',
			'font-style'     => 'Font Style',
			'letter-spacing' => 'Letter Spacing',
			'word-spacing'   => 'Word Spacing',
			'top'            => 'Top',
			'bottom'         => 'Bottom',
			'left'           => 'Left',
			'right'          => 'Right',
			'center'         => 'Center',
			'size'           => 'Size',
			'spacing'        => 'Spacing',
			'width'          => 'Width',
			'height'         => 'Height',
		);

		$is_responsive      = isset( $args['choices']['responsive'] ) && true === $args['choices']['responsive'];
		$responsive_choices  = ! $is_responsive && isset( $args['choices']['responsive_choices'] )
			? (array) $args['choices']['responsive_choices']
			: array();

		$new_choices = array(
			'responsive'         => $is_responsive,
			'responsive_choices' => $responsive_choices,
			'fields'             => array(),
		);

		// Only pass units if the theme explicitly registers them.
		if ( isset( $args['choices']['units'] ) && is_array( $args['choices']['units'] ) && ! empty( $args['choices']['units'] ) ) {
			$new_choices['units'] = $args['choices']['units'];
		}

		if ( isset( $args['default'] ) && is_array( $args['default'] ) ) {
			foreach ( $args['default'] as $choice => $default ) {
				$label = $choice;
				$label = isset( $labels[ $choice ] ) ? $labels[ $choice ] : $label;
				$label = isset( $args['choices']['labels'][ $choice ] ) ? $args['choices']['labels'][ $choice ] : $label;

				$new_choices['fields'][ $choice ] = array(
					'label'   => $label,
					'default' => $default,
				);
			}
		}

		$args['choices'] = $new_choices;

		return $args;
	}

	/**
	 * Sanitize the composite value.
	 */
	public static function sanitize( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		foreach ( $value as $key => $val ) {
			if ( is_array( $val ) ) {
				// Responsive value: { desktop: '10px', tablet: '8px', mobile: '6px' }
				foreach ( $val as $device => $device_val ) {
					$value[ $key ][ $device ] = sanitize_text_field( $device_val );
				}
			} else {
				$value[ $key ] = sanitize_text_field( $val );
			}
		}

		return $value;
	}
}
