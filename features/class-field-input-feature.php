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
	 * @param WP_Field_Base $field
	 * @param array $attributes
	 * @param null|callable|string $value
	 */
	function __construct( $field, $attributes = array(), $value = null ) {

		$this->field = $field;

		$attributes[ 'html_type' ] = $this->html_type();

		parent::__construct( $field, $attributes, $value );

	}

	/**
	 * @return mixed
	 */
	function html_value() {

		return $this->field->value();

	}
}
