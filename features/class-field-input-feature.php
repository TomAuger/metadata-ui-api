<?php
/**
 * Class WP_Field_Input_Feature
 */
class WP_Field_Input_Feature extends WP_Field_Feature_Base {

	/**
	 *
	 */
	const HTML_TAG = 'input';

	/**
	 * @return array
	 */
	function default_args() {
		return array(
			'input:html:type' => $this->html_type()
		);
	}

	/**
	 * @return mixed
	 */
	function html_value() {

		return $this->field->value();

	}
}
