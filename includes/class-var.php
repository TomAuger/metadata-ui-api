<?php

/**
 * Class WP_Class_Var
 */
class WP_Class_Var {

	/**
	 * @var
	 */
	var $var_name;

	/**
	 * @var
	 */
	var $var_type;

	/**
	 * @var
	 */
	var $auto_create = false;

	/**
	 * @var
	 */
	var $var_default;

	/**
	 * @var
	 */
	var $var_prefix;

	/**
	 * @var
	 */
	var $array_of;

	/**
	 * @var
	 */
	var $array_keys = array();

	/**
	 * @var
	 */
	var $custom_args = array();

	/**
	 * @param string $var_name
	 * @param array $args
	 */
	function __construct( $var_name, $args ) {

		$this->var_name = $var_name;

		$this->set_var_type( ! empty( $args['var_type'] ) ? $args['var_type'] : null );
		unset( $args['var_type'] );

		if ( class_exists( $this->var_type ) || ( $this->is_array() && class_exists( $this->array_of ) ) ) {

			if ( ! isset( $args['var_prefix'] ) ) {

				$this->var_prefix = $var_name;

			}

			//$args = array_merge( $this->get_default_args(), $args );

		}

		foreach ( $args as $arg_name => $arg_value ) {
			/*
			 * Now assign the remaining annotations to either...
			 */
			if ( property_exists( $this, $var_arg_name = "var_{$arg_name}" ) ) {

				/*
				 * ... the longer named property (unless it is 'custom'), or...
				 */
				$this->$var_arg_name = $arg_value;

			} else if ( property_exists( $this, $arg_name ) && 'custom' != $arg_name ) {

				/*
				 * ... the named property (unless it is 'custom'), or...
				 */
				$this->$arg_name = $arg_value;

			} else {

				/*
				 *  ... an array element of the $custom property.
				 */
				$this->custom[ $arg_name ] = $arg_value;

			}
		}

	}

//	function get_default_args() {
//		return array();
//	}

	/**
	 * @param string $var_type
	 */
	function set_var_type( $var_type ) {

		if ( empty( $var_type ) ) {

			$this->var_type = 'mixed';

		} else if ( preg_match( '#(.+)\[\]$#', $var_type, $match ) ) {

			$this->var_type = 'array';
			$this->array_of = $match[1];

		} else {

			$this->var_type = $args['var_type'];
		}

	}

	/**
	 * Test to see if the value in $var_type is a valid class name.
	 *
	 * @return bool
	 */
	function is_class() {

		return class_exists( $this->var_type );

	}

	/**
	 * Test to see if the value in $var_type represents an array.
	 *
	 * @return bool
	 */
	function is_array() {

		return 'array' == $this->var_type;

	}

}
