<?php
namespace ThimPress\Customizer\Control;

use ThimPress\Customizer\Modules\Base;

/**
 * Dimensions control – unified, single-<li> React-rendered control.
 */
class Dimensions extends Base {

	public $type = 'thim-dimensions';

	public function enqueue() {
		parent::enqueue();
	}

	public function to_json() {
		parent::to_json();

		if ( isset( $this->json['label'] ) ) {
			$this->json['label'] = html_entity_decode( $this->json['label'] );
		}

		if ( isset( $this->json['description'] ) ) {
			$this->json['description'] = html_entity_decode( $this->json['description'] );
		}
	}

	protected function content_template() {}
}
