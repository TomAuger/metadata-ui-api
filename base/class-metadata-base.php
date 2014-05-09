<?php
/**
 * Class WP_Metadata_Base
 */
abstract class WP_Metadata_Base {

	/**
	 * The property (var) prefix from a constant to be used for this current class.
	 *
	 * @example: const PREFIX = 'form_';
	 *
	 * Intended to be used by subclasses.
	 */
	const PREFIX = null;

	/**
	 * @var array
	 */
	var $args = array();

	/**
	 * @var array
	 */
	var $extra_args = array();

	/**
	 * @var array
	 */
	var $delegated_args = array();

	/**
	 * @var bool
	 */
	private $_done_once = array();

	/**
	 * Array of field names that should not get a prefix.
	 *
	 * Intended to be used by subclasses.
	 *
	 * @return array
	 */
	static function NO_PREFIX() {

		return array();

	}

	/**
	 * @return array
	 */
	static function DELEGATES() {

		return array();

	}

	/**
	 * @return array
	 */
	static function ABBREVIATIONS() {

		return array();

	}

	/**
	 * @return array
	 */
	static function TRANSFORMS() {

		return array();

	}

	/**
	 * @param array $args
	 */
	function __construct( $args = array() ) {

		if ( !isset( $this->_done_once[ $this_class = get_class( $this ) ] ) ) {
			$this->_done_once[ $this_class ] = true;
			$this->_call_lineage( 'initialize_class' );
		}

		$args = wp_parse_args( $args, $this->_call_lineage_value( 'default_args', array(), $args ) );

		if ( $this->_call_lineage_value( 'do_assign_args', true, $args ) ) {
			$args = $this->_call_lineage_collect_array_elements( 'pre_expand_args', $args );

			$args = $this->expand_args( $args );
			$args = $this->_call_lineage_collect_array_elements( 'pre_prefix_args', $args );

			$args = $this->prefix_args( $args );
			$args = $this->_call_lineage_collect_array_elements( 'pre_transform_args', $args );

			$args = $this->transform_args( $args );
			$args = $this->_call_lineage_collect_array_elements( 'pre_delegate_args', $args );

			$args = $this->delegate_args( $args );
			$args = $this->_call_lineage_collect_array_elements( 'pre_assign_args', $args );

			$this->args = $args;
			$this->_call_lineage( 'assign_args', $args );

		}

		$this->_call_lineage( 'initialize', $args );

	}

	/**
	 * Gets the property (var) prefix from a constant to be used for this current class.
	 *
	 * @example: const PREFIX = 'form_';
	 *
	 * Intended to be used by subclasses.
	 *
	 * @return array
	 */
	function get_prefix() {

		return $this->constant( 'PREFIX' );

	}

	/**
	 * Gets array of field names that should not get a prefix.
	 *
	 * @return array
	 */
	function get_no_prefix() {

		return $this->_call_lineage_collect_array_elements( 'NO_PREFIX' );

	}

	/**
	 * Returns an array of delegate properties and with their prefix as array key.
	 *
	 * Subclasses should define DELEGATES() function:
	 *
	 *    return array(
	 *      $prefix1 => $property_name1,
	 *      ...,
	 *      $prefixN => $property_nameN,
	 *    );
	 *
	 * @return array
	 */
	function get_delegates() {

		return $this->_call_lineage_collect_array_elements( 'DELEGATES' );

	}

	/**
	 * Returns an array of property abbreviations as array key and expansion as key vale.
	 *
	 * Subclasses should define DELEGATES() function:
	 *
	 *    return array(
	 *      $abbreviation1 => $expansion1,
	 *      ...,
	 *      $abbreviationN => $expansionN,
	 *    );
	 *
	 * @return array
	 */
	function get_abbreviations() {

		return $this->_call_lineage_collect_array_elements( 'ABBREVIATIONS' );

	}

	/**
	 * Returns an array of property abbreviations as array key and expansion as key vale.
	 *
	 * Subclasses should define DELEGATES() function:
	 *
	 *    return array(
	 *      $abbreviation1 => $expansion1,
	 *      ...,
	 *      $abbreviationN => $expansionN,
	 *    );
	 *
	 * @return array
	 */
	function get_transforms() {

		return $this->_call_lineage_collect_array_elements( 'TRANSFORMS' );

	}

	/**
	 * @param string $constant_name
	 * @param bool|string $class_name
	 *
	 * @return mixed
	 */
	function constant( $constant_name, $class_name = false ) {

		if ( !$class_name ) {
			$class_name = get_class( $this );
		}

		if ( !defined( $constant_ref = "{$class_name}::{$constant_name}" ) ) {
			$value = null;
		}
		else {
			$value = constant( $constant_ref );
		}

		return $value;

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function transform_args( $args ) {

		if ( count( $transforms = $this->get_transforms() ) ) {
			foreach ( $transforms as $regex => $result ) {
				foreach ( $args as $name => $value ) {
					if ( $match_count = preg_match( "#{$regex}#", $name, $matches ) ) {
						unset( $args[ $name ] );

						$name = $result;

						for ( $i = 1; $i <= $match_count; $i++ ) {
							$name = str_replace( "\${$i}", $matches[ $i ], $name );
						}

						$args[ $name ] = $value;
					}
				}
			}
		}

		return $args;

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function expand_args( $args ) {

		if ( count( $abbreviations = $this->get_abbreviations() ) ) {
			foreach ( $abbreviations as $abbreviation => $expansion ) {
				if ( isset( $args[ $abbreviation ] ) && !isset( $args[ $expansion ] ) ) {
					$args[ $expansion ] = $args[ $abbreviation ];

					unset( $args[ $abbreviation ] );
				}
			}
		}

		return $args;

	}

	/**
	 * Ensure all $args have the appropriate prefix.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	function prefix_args( $args ) {

		if ( false !== ( $delegate_prefix = $this->get_prefix() ) ) {
			$no_prefix = implode( '|', $this->get_no_prefix() );

			foreach ( $args as $name => $value ) {
				/**
				 * For every $arg passed-in that does not contain an underscore ('_') prefix it with
				 * value of PREFIX unless it's value is in NO_PREFIX.
				 */
				if ( false === strpos( $name, '_' ) && !preg_match( "#^({$no_prefix})$#", $name ) ) {
					$args[ "{$delegate_prefix}{$name}" ] = $value;

					unset( $args[ $name ] );
				}
			}
		}

		return $args;

	}

	/**
	 * Parse out $args for delegate properties from $args and store in $this->delegated_args array.
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	function delegate_args( $args ) {

		$class = get_class( $this ); // @todo Unused variable

		$delegates = $this->get_delegates();

		$this->delegated_args = WP_Metadata::extract_prefixed_args( $args, $delegates, 'strip_prefix=0' );

		return $args;

	}

	/**
	 * Assign the element values in the $args array to the properties of this object.
	 *
	 * @param array $args An array of name/value pairs that can be used to initialize an object's properties.
	 */
	function assign_args( $args ) {

		/*
		 * Assign the arg values to properties, if they exist.
		 * If no property exists capture value in the $this->extra[] array.
		 */
		foreach ( $args as $name => $value ) {
			if ( method_exists( $this, $method_name = "set_{$name}" ) ) {
				call_user_func( array( $this, $method_name ), $value );
			}
			elseif ( property_exists( $this, $name ) ) {
				$this->{$name} = $value;
			}
			elseif ( $this->_non_public_property_exists( $property_name = "_{$name}" ) ) {
				$this->{$property_name} = $value;
			}
			else {
				$this->extra_args[ $name ] = $value;
			}
		}

	}

	/**
	 * Allows methods without parameters to be accessed as if properties.
	 *
	 * @param string $property_name
	 *
	 * @return mixed|null
	 */
	function __get( $property_name ) {

		if ( method_exists( $this, $property_name ) ) {
			$value = call_user_func( array( $this, $property_name ) );
		}
		else {
			$message = __( 'Object of class %s does not contain a property or method named %s().' );
			trigger_error( sprintf( $message, get_class( $this ), $property_name ), E_USER_WARNING );
			$value = null;
		}

		return $value;

	}

	/**
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return mixed
	 */
	function __call( $method_name, $args = array() ) {

		$result = false;

		if ( preg_match( '#^the_(.*)$#', $method_name, $match ) ) {
			$method_exists = false;

			if ( method_exists( $this, $method_name = $match[ 1 ] ) ) {
				$method_exists = true;
			}
			elseif ( method_exists( $this, $method_name = "{$method_name}_html" ) ) {
				$method_exists = true;
			}
			elseif ( method_exists( $this, $method_name = "get_{$method_name}" ) ) {
				$method_exists = true;
			}
			elseif ( method_exists( $this, $method_name = "get_{$match[1]}" ) ) {
				$method_exists = true;
			}

			if ( $method_exists ) {
				echo call_user_func_array( array( $this, $method_name ), $args );

				$result = true;
			}
		}

		return $result;

	}

	/**
	 * Get an array of constants by class name for the current lineage.
	 *
	 * @param string $constant_name
	 * @param bool|string $class_name
	 *
	 * @return array
	 */
	private function _get_lineage_const_array( $constant_name, $class_name = false ) {

		if ( !$class_name ) {
			$class_name = get_class( $this );
		}

		$lineage = wp_get_class_lineage( $class_name, true );

		$constants = array();

		foreach ( $lineage as $ancestor ) {
			if ( defined( $constant_ref = "{$ancestor}::{$constant_name}" ) ) {
				$constants = array_merge( $constants, array( $this->constant( $constant_name, $ancestor ) ) );
			}
		}

		/*
		 * Remove any constants values that are null.
		 */

		// @todo Are anonymous functions available for PHP 5.2?
		return array_filter( $constants, function ( $element ) {

			return !is_null( $element );

		} );

	}

	/**
	 *
	 */
	protected function _non_public_property_exists( $property ) {

		$reflection = new ReflectionClass( get_class( $this ) );

		if ( !$reflection->hasProperty( $property ) ) {
			$exists = false;
		}
		else {
			$property = $reflection->getProperty( $property );
			$exists = $property->isProtected() || $property->isPrivate();
		}

		return $exists;

	}

	/**
	 * Call a named method starting with the most distant anscestor down to the current class filtering $value.
	 *
	 * @param string $method_name
	 * @param mixed $value
	 * @param array $args
	 *
	 * @return mixed
	 */
	private function _call_lineage_value( $method_name, $value, $args ) {

		$lineage = wp_get_class_lineage( get_class( $this ), true );

		foreach ( $lineage as $ancestor ) {
			if ( $this->_has_own_method( $ancestor, $method_name ) ) {
				$value = $this->_invoke_method( $ancestor, $this, $method_name, array( $value, $args ) );
			}
		}

		return $value;

	}

	/**
	 * Call a named method starting with the most distant anscestor down to the current class and merging $args.
	 *
	 * @param string $method_name
	 * @param array $elements
	 *
	 * @return array
	 */
	protected function _call_lineage_collect_array_elements( $method_name, $elements = array() ) {

		$lineage = wp_get_class_lineage( get_class( $this ), true );

		foreach ( $lineage as $ancestor ) {
			if ( $this->_has_own_method( $ancestor, $method_name ) ) {
				$elements = array_merge( $elements, $this->_invoke_method( $ancestor, $this, $method_name, array( $elements ) ) );
			}
		}

		return $elements;

	}

	/**
	 * Call a named method starting with the most distant anscestor down to the current class with no return value.
	 *
	 * @param string $method_name
	 * @param array $args
	 */
	private function _call_lineage( $method_name, $args = array() ) {

		$lineage = wp_get_class_lineage( get_class( $this ), true );

		foreach ( $lineage as $ancestor ) {
			if ( $this->_has_own_method( $ancestor, $method_name ) ) {
				$this->_invoke_method( $ancestor, $this, $method_name, array( $args ) );
			}
		}

	}

	/**
	 * @param string $class_name
	 * @param string $property_name
	 *
	 * @return bool
	 */
	private function _has_own_static( $class_name, $property_name ) {

		$has_own_static_property = false;

		if ( property_exists( $class_name, $property_name ) ) {
			$reflected_property = new ReflectionProperty( $class_name, $property_name );
			$has_own_static_property = $reflected_property->isStatic();
		}

		return $has_own_static_property;

	}

	/**
	 * Allow invoking of instance methods that are overridden by methods in a child class.
	 *
	 * This allows for methods as filters and actions without requiring them to call parent::method().
	 *
	 * @param string $class_name
	 * @param object $object
	 * @param string $method_name
	 *
	 * @return mixed
	 */
	private function _get_static( $class_name, $object, $method_name ) {

		$reflected_property = new ReflectionProperty( $class_name, $property_name ); // @todo $property_name not set

		return $reflected_property->getValue( $object );

	}

	/**
	 * @param string $class_name
	 * @param string $method_name
	 *
	 * @return bool
	 */
	private function _has_own_method( $class_name, $method_name ) {

		$has_own_method = false;

		if ( method_exists( $class_name, $method_name ) ) {
			$reflector = new ReflectionMethod( $class_name, $method_name );
			$has_own_method = $class_name == $reflector->getDeclaringClass()->name;
		}

		return $has_own_method;

	}

	/**
	 * Allow invoking of instance methods that are overridden by methods in a child class.
	 *
	 * This allows for methods as filters and actions without requiring them to call parent::method().
	 *
	 * @param string $class_name
	 * @param object $object
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return mixed
	 */
	private function _invoke_method( $class_name, $object, $method_name, $args ) {

		$reflected_class = new ReflectionClass( $class_name );
		$reflected_method = $reflected_class->getMethod( $method_name );

		return $reflected_method->invokeArgs( $object, $args );

	}

}