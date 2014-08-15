<?php
/**
 * Class WP_Field_View_Base
 * @mixin WP_Field_Base
 * @property WP_Field_Input_Feature $input
 * @property WP_Field_Label_Feature $label
 * @property WP_Field_Help_Feature $help
 * @property WP_Field_Message_Feature $message
 * @property WP_Field_Infobox_Feature $infobox
 *
 */
abstract class WP_Field_View_Base extends WP_View_Base {

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
	 * @return array
	 */
	static function TRANSFORMS() {

		static $transforms;
		if ( ! isset( $transforms ) ) {
			/**
			 * Create a regex to insure delegated and no_prefix $args are not matched
			 * nor are $args that contain underscores.
			 *
			 * @see     http://stackoverflow.com/a/5334825/102699 for 'not' regex logic
			 * @see     http://www.rexegg.com/regex-lookarounds.html for negative lookaheads
			 * @see     http://ocpsoft.org/tutorials/regular-expressions/and-in-regex/ for logical 'and'
			 * @see     http://www.regular-expressions.info/refadv.html for "Keep text out of the regex match"
			 *
			 * @example Regex if 'foo' and 'bar' are no_prefix or contained:
			 *
			 *  '^(?!(?|foo|bar)$)(?!.*_)(.*)'
			 *
			 * @note    Other similar regex that might work the same
			 *
			 *  '^(?!foo$)(?!bar$)(?!.*_)(.*)';
			 *  '^(?!(?>(foo|bar))$)(?!.*_)(.*)'
			 *  '^(?!\K(foo|bar)$)(?!.*_)(.*)'
			 *
			 * Example matches any string except 'foo', 'bar' or one containing an underscore ('_').
			 */

			$properties = self::PROPERTIES();

			$features = implode( '|', $properties['features']['keys'] );

			$attributes = implode( '|', array_keys( array_merge( WP_Metadata::get_html_attributes( 'input' ) ) ) );

			$transforms = array(
				"^({$features}):(.+)$"                           => 'features[$1]:$2',
				"^features\[([^]]+)\]:({$attributes})$"          => 'features[$1]:html:$2',
				"^features\[([^]]+)\]:wrapper:({$attributes})$"  => 'features[$1]:wrapper:html:$2',
			);
		}

		return $transforms;

	}

	/**
	 * @return array
	 */
	static function PROPERTIES() {

		return array(
			'field' => array( 'type' => 'WP_Field_Base', 'auto_create' => false ),
			'wrapper' => array( 'type' => 'WP_Html_Element', 'html_tag' => 'div' ),
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

		$this->owner = $field;

	}

	/**
	 * @param $args
	 */
	function initialize( $args ) {
		/**
		 * @var WP_Field_Label_Feature $label
		 */
		if ( is_object( $label = $this->features['label'] ) ) {
			$label->element->set_attribute_value( 'for', $this->features['input']->element->get_id() );
		}

	}

	function initial_element_id() {

		return $this->field->field_name . '-metadata-field';

	}

	/**
	 * @return bool|string
	 */
	function initial_element_class() {

		return "metadata-field";

	}

	/**
	 * Delegate to $field explicitly since it is defined in base class.
	 * @return array
	 */
	function get_prefix() {
		/**
		 * @var WP_Metadata_Base $field
		 */
		$field = $this->field;

		if ( ! $field->has_annotated_property( $field->field_name ) ) {

			$prefix = false;

		} else {

			$prefix = $field->get_annotated_property( $field->field_name )->prefix;

		}

		return $prefix;
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
			$this->features['input'] = new WP_Field_Input_Feature( $this->field->view );

		}

		return $this->features['input'];

	}

	/**
	 * Convenience so users can use a more specific name than get_html().
	 *
	 * @return string
	 */
	function get_field_html() {

		return $this->get_html();

	}

	/**
	 * @return string
	 */
	function get_element_html() {

		return $this->get_features_html();

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

	function set_element_attribute( $attribute_name, $value ) {

		/**
		 * @var WP_Field_Label_Feature $input
		 */
		$input = $this->features[ 'input' ];

		$input->set_element_attribute( $attribute_name, $value );

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
		), $args ) : parent::__call( $method_name, $args );

	}

	/**
	 * @param string $property_name
	 *
	 * @return bool
	 */
	function __isset( $property_name ) {

		return isset( $this->features[ $property_name ] );

	}

}
