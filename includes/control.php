<?php

/**
 * Class WP_Control
 *
 * @mixin WP_Control_Data
 * @mixin WP_Control_Tags
 */
class WP_Control extends WP_Item {

	/**
	 *
	 */
	const CONTROL_TYPE = 'null';

	/**
	 *
	 */
	const ITEM_TYPE = 'control';


	/**
	 * @var bool|string
	 */
	var $control_name;

	/**
	 * @var bool|string
	 */
	var $control_type;

	/**
	 * @var bool|WP_Object_Type
	 */
	var $object_type = false;

	/**
	 * @param string $control_name
	 * @param array $control_args
	 */
	function __construct( $control_name, $control_args = array() ) {

		$this->control_name = $control_name;
		parent::__construct( $control_args );
	}

	/**
	 * @param array $args
	 * @param string $class_name
	 *
	 * @return WP_Object
	 */
	static function make_new( $args = array(), $class_name = null ) {
		$args = wp_parse_args( $args, array(
		    'control_name' => 'unspecified',
		));

		if ( ! $class_name ) {
			$new = new static( $args[ 'control_name' ], $args );
		} else {
			$new = new $class_name( $args[ 'control_name' ], $args );
		}

		return $new;

	}

	/**
	 * @param string $property_name
	 *
	 * @return mixed
	 */
	function __get( $property_name ) {

		if ( $this->tags->has_property( $property_name ) ) {

			$value = $this->tags->get_property( $property_name );

		} else if ( $this->data->has_property( $property_name ) ) {

			$value = $this->data->get_property( $property_name );

		}
		return $value;

	}

	/**
	 * @param string $property_name
	 * @param mixed $value
	 */
	function __set( $property_name, $value ) {

		if ( $this->tags->has_property( $property_name ) ) {

			$this->tags->set_property( $property_name, $value );

		} else if ( $this->data->has_property( $property_name ) ) {

			$this->data->set_property( $property_name, $value );

		}

	}

	/**
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return mixed
	 */
	function __call( $method_name, $args = array() ) {
		$value = null;

		if ( $this->tags->has_method( $method_name ) ) {

			$value = $this->tags->call_method( $method_name, $args );

		} else if ( $this->tags->has_method( $get_method = preg_replace( '#^the_#', 'get_', "{$method_name}_html" ) ) ) {

			echo $value = $this->tags->call_method( $get_method, $args );

		} else if ( $this->tags->has_method( $get_method = preg_replace( '#_html$#', '', $get_method ) ) ) {

			echo $value = $this->tags->call_method( $get_method, $args );

		} else if ( $this->data->has_method( $method_name ) ) {

			echo $value = $this->data->call_method( $method_name, $args );

		} else if ( $this->data->has_method( $get_method = preg_replace( '#^the_#', 'get_', $method_name ) ) ) {

			echo $value = $this->data->call_method( $get_method, $args );

		} else {

			$message = __( 'ERROR: No method %s exists for class %s or in its data or its tags.', 'wp-metadata' );
			trigger_error( sprintf( $message, $method_name, get_class( $this ) ) );

		}

		return $value;

	}

}

class WP_Control_Data extends WP_Data {

	/**
	 * @var string|WP_Section
	 */
	var $section;

	/**
	 * @var string|WP_Storage
	 */
	var $storage;

	/**
	 * @var bool
	 */
	var $sanitizer;

	/**
	 * @var bool
	 */
	var $validator;

	/**
	 * @var array
	 */
	var $args;

	/**
	 * @var bool
	 */
	var $required;

	/**
	 * @var mixed
	 */
	var $default;

	/**
	 * @return WP_Control
	 */
	function as_control() {
		$this->item;
	}

}

class WP_Control_Tags extends WP_Tags {

	/**
	 * @return string
	 */
	function get_html() {
		return '@todo Control output goes here';
	}

	/**
	 * @return WP_Control
	 */
	function as_control() {
		$this->item;
	}

}
