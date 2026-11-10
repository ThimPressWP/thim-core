<?php
namespace ThimPress\Customizer\Control;

use ThimPress\Customizer\Modules\Base;

/**
 * Multicolor control.
 */
class Multicolor extends Base {

	/**
	 * The control type.
	 *
	 * @access public
	 * @var string
	 */
	public $type = 'thim-multicolor';

	/**
	 * Enqueue control related scripts/styles.
	 *
	 * @access public
	 */
	public function enqueue() {
		parent::enqueue();
	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @access public
	 */
	public function to_json() {
		parent::to_json();

		$this->json['choices']['swatches'] = $this->color_swatches();

		if ( isset( $this->json['label'] ) ) {
			$this->json['label'] = html_entity_decode( $this->json['label'] );
		}

		if ( isset( $this->json['description'] ) ) {
			$this->json['description'] = html_entity_decode( $this->json['description'] );
		}
	}

	public function color_swatches() {
		$default_swatches = array(
			'#000000',
			'#ffffff',
			'#dd3333',
			'#dd9933',
			'#eeee22',
			'#81d742',
			'#1e73be',
			'#8224e3',
		);

		$default_swatches = apply_filters( 'thim_customizer_default_color_swatches', $default_swatches );

		$defined_swatches = isset( $this->choices['swatches'] ) && ! empty( $this->choices['swatches'] ) ? $this->choices['swatches'] : array();

		if ( empty( $defined_swatches ) ) {
			$defined_swatches = isset( $this->choices['palettes'] ) && ! empty( $this->choices['palettes'] ) ? $this->choices['palettes'] : array();
		}

		if ( ! empty( $defined_swatches ) ) {
			$swatches       = $defined_swatches;
			$total_swatches = count( $swatches );

			if ( $total_swatches < 8 ) {
				for ( $i = $total_swatches; $i <= 8; $i++ ) {
					$swatches[] = $total_swatches[ $i ];
				}
			}
		} else {
			$swatches = $default_swatches;
		}

		$swatches = apply_filters( 'thim_customizer_color_swatches', $swatches );

		return $swatches;
	}


	/**
	 * An Underscore (JS) template for this control's content (but we use React).
	 */
	protected function content_template() {}
}
