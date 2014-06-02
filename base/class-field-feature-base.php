<?php
/**
 * Class WP_Field_Feature_Base
 */
abstract class WP_Field_Feature_Base extends WP_Metadata_Base {

	/**
	 *
	 */
	const HTML_TAG = 'div';

	/**
	 *
	 */
	const HTML_TYPE = false;

	/**
	 *
	 */
	const WRAPPER_TAG = 'div';

	/**
	 * @var string
	 */
	var $feature_type;

	/**
	 * @var WP_Field_Base
	 */
	var $field;

	/**
	 * @var WP_Html_Element
	 */
	var $wrapper;

	/**
	 * @var WP_Html_Element
	 */
	var $element;

	/**
	 * @return array
	 */
	static function DELEGATES() {

		return array(
			'html' => 'element',
			'wrapper' => 'wrapper',
		);
	}

	/**
	 * @return array
	 */
	static function NO_PREFIX() {

		return array(
			'field',
			'element',
			'wrapper',
		);
	}

	/**
	 * @return array
	 */
	static function TRANSFORMS() {

		return array(
			'^wrapper_([^_]+)$' => 'wrapper_html_$1', // e.g. Allow wrapper_class as shortcut to wrapper_html_class.
		);
	}

	/**
	 * @param WP_Field_Base $field
	 * @param array $feature_args
	 */
	function __construct( $field, $feature_args = array() ) {

		//$this->field =
		$feature_args[ 'field' ] = $field;

		parent::__construct( $feature_args );
	}

	function initialize( $feature_args ) {

		if ( ! is_object( $this->element ) ) {
			$html_attributes = WP_Metadata::extract_prefixed_args( $feature_args, 'html' );

			$html_attributes[ 'id' ] = $this->html_id();
			$html_attributes[ 'name' ] = $this->html_name();
			$html_attributes[ 'class' ] = $this->html_class() . ( !empty( $html_attributes[ 'class' ] ) ? " {$html_attributes['class']}" : '' );
			$this->element = WP_Metadata::get_html_element( $this->html_tag(), $html_attributes );
		}

		if ( !is_object( $this->wrapper ) ) {
			$wrapper_attributes = WP_Metadata::extract_prefixed_args( $feature_args, 'wrapper_html' );

			$wrapper_attributes[ 'id' ] = $this->wrapper_html_id();
			$wrapper_attributes[ 'name' ] = $this->wrapper_html_name();
			$wrapper_attributes[ 'class' ] = $this->wrapper_html_class() . ( !empty( $wrapper_attributes[ 'class' ] ) ? " {$wrapper_attributes['class']}" : '' );
			$this->wrapper = WP_Metadata::get_html_element( $this->wrapper_tag(), $wrapper_attributes );
		}

	}

	/**
	 * Return the HTML tag to be used by this class.
	 * @return array
	 */
	function html_tag() {

		/**
		 * Ask the field if it has a specific HTML_TAG (i.e. "textarea", "select", etc.)
		 */
		$html_tag = 'input' == $this->feature_type ? $this->field->constant( 'HTML_TAG' ) : false;

		/**
		 * If no, ask the feature what HTML TAG to use (probably 'input').
		 */

		return $html_tag ? $html_tag : $this->constant( 'HTML_TAG' );

	}

	/**
	 * @return bool|string
	 */
	function html_id() {

		return str_replace( '_', '-', $this->field->view->html_id() ) . "-field-{$this->feature_type}";

	}

	/**
	 * @return bool|string
	 */
	function html_class() {

		return "field-feature field-{$this->feature_type}";

	}

	/**
	 * @return bool|string
	 */
	function html_name() {

		return 'wp_metadata_forms[' . $this->field->form_html_name() . '][' . $this->field->view->html_name() . ']';

	}

	/**
	 * Return the HTML tag to be wrapper around the field.
	 * @return array
	 */
	function wrapper_tag() {

		return $this->constant( 'WRAPPER_TAG' );

	}

	/**
	 * @return bool|string
	 */
	function wrapper_html_id() {

		return $this->html_id() . "-wrapper";

	}

	/**
	 * @return bool|string
	 */
	function wrapper_html_class() {

		return $this->html_class() . "-wrapper";

	}

	/**
	 * @return bool|string
	 */
	function wrapper_html_name() {

		return $this->html_name() . "-wrapper";

	}

	/**
	 * @return bool|string
	 */
	function html_type() {

		return $this->field->constant( 'HTML_TYPE' );

	}

	/**
	 * @return mixed
	 */
	function html_value() {

		return __( 'No html_value() method in Field Feature' );

	}

	/**
	 * @return string
	 */
	function get_element_html() {

		$this->element->element_value = $this->html_value();

		return $this->element->get_element_html();

	}

	/**
	 * @return string
	 */
	function get_feature_html() {

		$this->wrapper->element_value = $this->get_element_html();

		$feature_html = $this->wrapper->get_element_html();

		return $feature_html;

	}

	/**
	 * @param string $attribute_name
	 *
	 * @return mixed
	 */
	function get_html_attribute( $attribute_name ) {

		return $this->element->get_attribute( $attribute_name );

	}

	/**
	 * @param string $attribute_name
	 * @param mixed $value
	 */
	function set_html_attribute( $attribute_name, $value ) {

		$this->element->set_attribute( $attribute_name, $value );

	}

}
