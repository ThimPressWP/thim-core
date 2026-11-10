<?php
namespace ThimPress\Customizer\Control;

use ThimPress\Customizer\Modules\Base;

class LayoutBuilder extends Base {

	public $type = 'thim-builder';

	/**
	 * Default layout value.
	 *
	 * @var array
	 */
	public $default = array();

	/**
	 * Input attributes (devices, rows, zones, row_labels, zone_labels).
	 *
	 * @var array
	 */
	public $input_attrs = array();

	/**
	 * Pass data to JavaScript.
	 */
	public function to_json() {
		// Temporarily clear input_attrs so parent::to_json() does not iterate nested
		// arrays through esc_attr() — which triggers "Array to string conversion"
		// notices with WP_DEBUG enabled, corrupting the JSON output.
		$input_attrs       = $this->input_attrs;
		$this->input_attrs = array();

		parent::to_json();

		$this->input_attrs         = $input_attrs;
		$this->json['input_attrs'] = $this->input_attrs;
		$this->json['choices']     = $this->choices;
	}

	/**
	 * Empty - React handles rendering.
	 */
	public function render_content() {}

	/**
	 * Empty - React handles rendering.
	 */
	protected function content_template() {}

	/**
	 * Enqueue builder script and localize choices data.
	 */
	public function enqueue() {
		parent::enqueue();

		// Use input_attrs['group'] as the choices key, falling back to the control ID.
		$group_key    = isset( $this->input_attrs['group'] ) ? $this->input_attrs['group'] : $this->id;
		$choices_json = wp_json_encode( $this->choices );

		wp_add_inline_script(
			'thim-customizer-control',
			'(function(){
				if ( ! window.thimBuilderControlsData ) {
					window.thimBuilderControlsData = { choices: {} };
				}
				window.thimBuilderControlsData.choices[' . wp_json_encode( $group_key ) . '] = ' . $choices_json . ';
			})();',
			'before'
		);
	}
}
