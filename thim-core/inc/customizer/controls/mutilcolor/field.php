<?php
namespace ThimPress\Customizer\Field;

use ThimPress\Customizer\Modules\Field;

class Multicolor extends Field {

	public $type = 'thim-multicolor';

	protected $control_class = '\ThimPress\Customizer\Control\Multicolor';

	protected $control_has_js_template = true;

	public function init( $args ) {
		add_filter( 'thim_customizer_output_control_classnames', array( $this, 'output_control_classnames' ) );
	}

	/**
	 * Filter preferred choice setting.
	 *
	 * @param string $setting The setting.
	 * @param string $choice  The choice.
	 * @param array  $args    The arguments.
	 * @return mixed
	 */
	public function filter_preferred_choice_setting( $setting, $choice, $args ) {
		if ( ! isset( $args[ $setting ] ) ) {
			return '';
		}

		if ( null === $choice ) {
			$per_choice_found = false;

			foreach ( $args['choices'] as $choice_id => $choice_label ) {
				if ( isset( $args[ $setting ][ $choice_id ] ) ) {
					$per_choice_found = true;
					break;
				}
			}

			if ( ! $per_choice_found ) {
				return $args[ $setting ];
			}

			return '';
		}

		if ( isset( $args[ $setting ][ $choice ] ) ) {
			return $args[ $setting ][ $choice ];
		}

		foreach ( $args['choices'] as $id => $set ) {
			if ( $id !== $choice && isset( $args[ $setting ][ $id ] ) ) {
				unset( $args[ $setting ][ $id ] );
			} elseif ( ! isset( $args[ $setting ][ $id ] ) ) {
				$args[ $setting ] = '';
			}
		}

		return $args[ $setting ];
	}

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
	 * Filter control args.
	 *
	 * @param array                $args         The field arguments.
	 * @param WP_Customize_Manager $wp_customize The customizer instance.
	 * @return array
	 */
	public function filter_control_args( $args, $wp_customize ) {
		if ( $args['id'] !== $this->args['id'] ) {
			return $args;
		}

		$args = parent::filter_control_args( $args, $wp_customize );

		$use_alpha = $this->filter_preferred_choice_setting( 'alpha', null, $args ) ? true : false;
		$swatches  = $this->filter_preferred_choice_setting( 'swatches', null, $args );
		$swatches  = empty( $swatches ) ? $this->filter_preferred_choice_setting( 'palettes', null, $args ) : $swatches;
		$swatches  = empty( $swatches ) ? array() : $swatches;

		if ( empty( $swatches ) ) {
			$swatches = isset( $args['palettes'] ) && ! empty( $args['palettes'] ) ? $args['palettes'] : array();
		}

		$new_choices = array(
			'labelStyle'    => isset( $args['choices']['labelStyle'] ) ? $args['choices']['labelStyle'] : 'tooltip',
			'formComponent' => isset( $args['choices']['formComponent'] ) ? $args['choices']['formComponent'] : '',
		);

		if ( ! empty( $swatches ) ) {
			$new_choices['swatches'] = $swatches;
		}

		foreach ( $args['choices'] as $choice => $choice_label ) {
			if ( 'swatches' === $choice || 'palettes' === $choice || 'labelStyle' === $choice || 'formComponent' === $choice ) {
				continue;
			}

			if ( is_array( $choice_label ) ) {
				continue;
			}

			$use_alpha_per_choice = $this->filter_preferred_choice_setting( 'alpha', $choice, $args ) ? true : $use_alpha;
			$swatches_per_choice  = $this->filter_preferred_choice_setting( 'swatches', $choice, $args );
			$swatches_per_choice  = empty( $swatches_per_choice ) ? $this->filter_preferred_choice_setting( 'palettes', $choice, $args ) : $swatches_per_choice;
			$swatches_per_choice  = empty( $swatches_per_choice ) ? $swatches : $swatches_per_choice;

			$choice_args = array(
				'label'   => $choice_label,
				'alpha'   => $use_alpha_per_choice,
				'default' => $this->filter_preferred_choice_setting( 'default', $choice, $args ),
			);

			if ( ! empty( $swatches_per_choice ) ) {
				$choice_args['swatches'] = $swatches_per_choice;
			}

			$new_choices[ $choice ] = $choice_args;
		}
		$args['choices'] = $new_choices;

		return $args;
	}

	public static function sanitize( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		foreach ( $value as $key => $subvalue ) {
			$value[ $key ] = \ThimPress\Customizer\Field\Color::sanitize( $subvalue );
		}

		return $value;
	}

	public function output_control_classnames( $classnames ) {
		$classnames['thim-multicolor'] = '\ThimPress\Customizer\CSS\Multicolor';

		return $classnames;
	}
}
