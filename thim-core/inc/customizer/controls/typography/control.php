<?php
namespace ThimPress\Customizer\Control;

use ThimPress\Customizer\Modules\Base;

/**
 * Typography control – unified, single-<li> React-rendered control.
 */
class Typography extends Base {

	public $type = 'thim-typography';

	public function enqueue() {
		parent::enqueue();
	}

	public function to_json() {
		parent::to_json();
		
		$this->json['default'] = $this->setting ? $this->setting->default : null;

		if ( isset( $this->default ) ) {
			$this->json['default'] = $this->default;
		}

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

		$defined_swatches = array();
		if ( isset( $this->choices['swatches'] ) && ! empty( $this->choices['swatches'] ) ) {
			$defined_swatches = $this->choices['swatches'];
		} elseif ( isset( $this->choices['color_swatches'] ) && ! empty( $this->choices['color_swatches'] ) ) {
			$defined_swatches = $this->choices['color_swatches'];
		} elseif ( isset( $this->choices['palettes'] ) && ! empty( $this->choices['palettes'] ) ) {
			$defined_swatches = $this->choices['palettes'];
		}

		if ( ! empty( $defined_swatches ) ) {
			$swatches       = $defined_swatches;
			$total_swatches = count( $swatches );

			if ( $total_swatches < 8 ) {
				for ( $i = $total_swatches; $i < 8; $i++ ) {
					$swatches[] = $default_swatches[ $i ];
				}
			}
		} else {
			$swatches = $default_swatches;
		}

		$swatches = apply_filters( 'thim_customizer_color_swatches', $swatches );

		return $swatches;
	}
	protected function content_template() {}
}
