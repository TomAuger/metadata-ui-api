<?php
/**
 * Class WP_Field_View_Base
 * @mixin WP_Field_Base
 * @property WP_Field_Input_Feature $input
 * @property WP_Field_Label_Feature $label
 * @property WP_Field_Help_Feature $help
 * @property WP_Field_Message_Feature $message
 * @property WP_Field_Infobox_Feature $infobox
 */
abstract class WP_Field_View_Base extends WP_Metadata_Base {

	/**
	 *
	 */
	const WRAPPER_TAG = 'div';

	/**
	 * @var string
	 */
	var $view_name;

	/**
	 * @var WP_Field_Base
	 */
	var $field;

	/**
	 * @var bool|array
	 */
	var $features = false;

	/**
	 * @var WP_Html_Element
	 */
	var $wrapper;

	/**
	 * @return array
	 */
	static function PROPERTIES() {

		return array(
	    'features' => array(
	      'type' => 'WP_Field_Feature_Base[]',
	      'default' => '$key_name',
	      'registry' => 'field_feature_types',
	      'keys' => array(
				  'label',
					'input',
					'help',
					'message',
					'infobox',
	      ),
    ));

 }

	/**
	  * Defines the PARAMETERS for the static class factory method 'make_new'.
	  *
	  * @return array
	  */
	 static function PARAMETERS() {

	   return array(
	     '$value',
	     '$parent',
	     '$args',
	   );

	 }

	/**
	 * @param string $view_name
	 * @param WP_Field_Base|null $field
	 * @param array $view_args
	 *
	 * @return WP_Field_View
	 *
	 */
	static function make_new( $view_name, $field, $view_args = array() ) {

		$form = new WP_Field_View( $view_name, $field, $view_args );

		return $form;

	}

	/**
	 * @param string $view_name
	 * @param WP_Field_Base|null $field
	 * @param array $view_args
	 */
	function __construct( $view_name, $field, $view_args = array() ) {

		$this->view_name = $view_name;

		if ( is_object( $field ) ) {

			$field->view = $this;

		}

		$this->field = $field;

		parent::__construct( $view_args );

		if ( !is_object( $this->wrapper ) ) {
			$wrapper_attributes = $this->extract_prefixed_args( 'wrapper', $view_args );

			$wrapper_attributes[ 'class' ] = $this->wrapper_html_class() . ( !empty( $wrapper_attributes[ 'class' ] ) ? "{$wrapper_attributes['class']} " : '' );

			$this->wrapper = WP_Metadata::get_html_element( $this->wrapper_tag(), $wrapper_attributes );
		}

	}

	/**
	 * @param $args
	 */
	function initialize( $args ) {

		if ( is_object( $label = $this->label ) ) {
			$label->set_html_attribute( 'for', $this->input->html_id() );
		}

	}

	/**
	 * @param string $feature_type
	 *
	 * @return array
	 */
	function get_feature_args( $feature_type ) {

		$feature_args = array_merge( $this->field->delegated_args[ $feature_type ], $this->delegated_args[ $feature_type ] );
		$feature_args[ 'field' ] = $this->field;

		return $feature_args;

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

		return str_replace( '_', '-', $this->field->field_name ) . '-' . $this->wrapper_html_class();

	}

	/**
	 * @return bool|string
	 */
	function wrapper_html_class() {

		return "metadata-field-wrapper";

	}

	/**
	 * @return bool|string
	 */
	function wrapper_html_name() {

		return "{$this->field->field_name}-wrapper";

	}

	/**
	 * Delegate to $field explicitly since it is defined in base class.
	 * @return array
	 */
	function get_prefix() {

		return $this->field->get_prefix();

	}

	/**
	 * Delegate to $field explicitly since it is defined in base class.
	 * @return array
	 */
	function get_no_prefix() {

		return $this->field->get_no_prefix();

	}

	/**
	 * Gets array of field feature type names
	 *
	 * @return array
	 */
	function get_feature_types() {

		$features = $this->get_annotated_property( 'features' );
		return is_array( $features->keys ) ? $features->keys : array();

	}

	/**
	 * Delegate accesses for missing poperties to the $field property
	 *
	 * @param string $property_name
	 *
	 * @return mixed
	 */
	function __get( $property_name ) {

		return isset( $this->features[ $property_name ] ) ? $this->features[ $property_name ] : ( property_exists( $this->field, $property_name ) ? $this->field[ $property_name ] : null );

	}

	/**
	 * Delegate accesses for missing poperties to the $field property
	 *
	 * @param string $property_name
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	function __set( $property_name, $value ) {

		return isset( $this->features[ $property_name ] ) ? $this->features[ $property_name ] = $value : ( property_exists( $this->field, $property_name ) ? $this->field->$property_name = $value : null );

	}

	/**
	 * Delegate calls for missing methods to the $field property
	 *
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return mixed
	 */
	function __call( $method_name, $args = array() ) {

		return method_exists( $this->field, $method_name ) ? call_user_func_array( array(
			$this->field,
			$method_name
		), $args ) : null;

	}

	/**
	 * @param string $property_name
	 *
	 * @return bool
	 */
	function __isset( $property_name ) {

		return isset( $this->features[ $property_name ] );

	}

	/**
	 * @param array $attributes
	 *
	 * @return array
	 */
	function filter_html_attributes( $attributes ) {

		return $attributes;

	}

	/**
	 *  Allow Input HTML to be overridden in Field or Field View
	 *
	 *  To override in Field, implement get_input_html().
	 *  To override in Field View, implement get_input_html().
	 *
	 */
	function get_input_html() {

		if ( method_exists( $this->field, 'get_input_html' ) ) {

			$input_html = $this->field->get_input_html();

		} else {

			$input_html = $this->input_feature()->get_feature_html();

		}

		return $input_html;
	}

	/**
	 * @return WP_Field_Feature_Base
	 */
	function input_feature() {

		if ( ! isset( $this->features['input'] ) ) {

			// Do this to ensure the return value of input_feature() can be dereferenced. Should never be needed.
			$this->features['input'] = new WP_Field_Input_Feature( $this->field );

		}

		return $this->features['input'];

	}

	/**
	 * @return array
	 */
	function get_features_html() {

		$features_html = array();

		foreach ( $this->get_feature_types() as $feature_type ) {
			/**
			 * @var WP_Field_Feature_Base $feature
			 */
			$feature = $this->features[ $feature_type ];

			if ( 'input' == $feature_type ) {

				$features_html[ $feature_type ] = $this->get_input_html();

			} else {

				$features_html[ $feature_type ] = $feature->get_feature_html();

			}

		}

		return implode( "\n", $features_html );

	}

	/**
	 * @return string
	 */
	function get_field_html() {

		$this->wrapper->element_value = $this->get_features_html();

		$feature_html = $this->wrapper->get_element_html();

		return $feature_html;

	}

	function set_html_attribute( $attribute_name, $value ) {

		/**
		 * @var WP_Field_Label_Feature $input
		 */
		$input = $this->features[ 'label' ];

		$input->set_html_attribute( $attribute_name, $value );

	}

	/**
	 * Return the HTML tag to be used by this class.
	 * @return array
	 */
	function html_tag() {

		return $this->constant( 'HTML_TAG' );

	}

	/**
	 * @return bool|string
	 */
	function html_id() {

		return str_replace( '_', '-', $this->html_name() ) . '-' . $this->html_class();

	}

	/**
	 * @return bool|string
	 */
	function html_class() {

		return "metadata-form";

	}

	/**
	 * @return bool|string
	 */
	function html_name() {

		return $this->field->field_name;

	}

}
