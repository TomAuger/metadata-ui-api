<?php

/**
 * Class WP_Object
 *
 * This is the "base" class for all other classes in the WP_Metadata 'feature-as-plugin" except for the "core" classes
 * in the /core/ directory or classes whose base class ultimately extends this class.
 *
 * Although WP_Object's name implies it is only designed for use by the #wp-metadata feature-as-plugin this
 * class can be used throughout WordPress anywhere there would be a significant benefit to using an OOP-based object
 * hiearchy.
 *
 */
class WP_Object {

	/**
	 * @var
	 */
	var $custom_args = array();

	/**
	 * @var
	 */
	private $_args = array();

	/**
	 * @var
	 */
	private static $_class_vars = array();


	/**
	 * @param array $args
	 */
	function __construct( $args = array() ) {

		$this->initialize_class();

		if ( $this->do_process_args( $args ) ) {

			$args = $this->set_arg_defaults( $args );

			$args = $this->expand_args( $args );

			$this->_args = $args = $this->collect_args( $args );

			$this->assign_args( $args );

			$this->initialize( $this->pre_initialize( $args ) );

			if ( ! WP_DEBUG ) {

				$this->_args = null;

			}

		}

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function set_arg_defaults( $args ) {

		return $this->apply_class_filters( 'set_arg_defaults', $args );

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function expand_args( $args ) {

		return $this->apply_class_filters( 'expand_args', $args );

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function collect_args( $args ) {

		return $this->apply_class_filters( 'collect_args', $args );

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function assign_args( $args ) {

		$this->do_class_action( 'assign_args', $args );

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function do_process_args( $args ) {

		return $this->apply_class_filters( 'do_process_args', true, $args );

	}

	/**
	 * @param $args
	 *
	 * @return mixed
	 */
	function pre_initialize( $args ) {

		return $this->apply_class_filters( 'pre_initialize', $args );

	}

	/**
	 * @param $args
	 *
	 * @return mixed
	 */
	function initialize( $args ) {

		$this->do_class_action( 'initialize', true, $args );

	}

	function initialize_class() {

		if ( ! isset( self::$_class_vars[ get_class( $this ) ] ) ) {

			self::$_class_vars[ get_class( $this ) ] = array();

			WP_Metadata::add_class_action( $this, 'initialize_class' );
			WP_Metadata::do_class_action( $this, 'initialize_class' );


		}

	}

	/**
	 *
	 */
	function _initialize_class() {

		$this->add_class_vars( array_fill_keys( array_keys( get_class_vars( get_class( $this ) ) ), array() ) );
		WP_Metadata::add_class_action( $this, 'assign_args' );

	}

	/**
	 * @param array $vars
	 */
	function add_class_vars( $vars ) {

		foreach( $vars as $var_name => $var_args ) {

			$this->add_class_var( $var_name, $var_args );

		}

	}

	/**
	 * @param string $var_name
	 * @param array $var_args
	 */
	function add_class_var( $var_name, $var_args ) {

		self::$_class_vars[ get_class( $this ) ][ $var_name ] = new WP_Class_Var( $var_name, $var_args );

	}

	/**
	 * @param string $var_name
	 * @return bool
	 */
	function has_class_var( $var_name ) {

		$has_var = null;
		if ( isset( self::$_class_vars[ $class_name = get_class( $this ) ] ) ) {

			$has_var = isset( self::$_class_vars[ $class_name ][ $var_name ] );

		}
		return $has_var;

	}

	/**
	 * @param string $var_name
	 * @return mixed
	 */
	function get_class_var( $var_name ) {

		$var = null;
		if ( isset( self::$_class_vars[ $class_name = get_class( $this ) ] ) ) {

			$class_vars = self::$_class_vars[ $class_name ];
			if ( isset( $class_vars[ $var_name ] ) ) {
				$var = $class_vars[ $var_name ];
			}

		}
		return $var;

	}

	/**
	 * Add a filter scoped to a class.
	 *
	 * @param string $filter
	 * @param int $priority
	 *
	 * @return mixed
	 */
	function add_class_filter( $filter, $priority = 10 ) {

		WP_Metadata::add_class_filter( $this, $filter, $priority  );

	}

	/**
	 * Add an action scoped to a class.
	 *
	 * @param array|string|callable $action
	 * @param int $priority
	 *
	 * @return mixed
	 */
	function add_class_action( $action, $priority = 10 ) {

		/*
		 * Call ::add_class_filter() not add_class_action() because the later just calls the former...
		 */
		WP_Metadata::add_class_filter( $this, $action, $priority );

	}

	/**
	 * @param callable|string $action
	 * @param mixed $arg1
	 * @param mixed $arg2
	 * @param mixed $arg3
	 * @param mixed $arg4
	 * @param mixed $arg5
	 *
	 * @return mixed
	 */
	function do_class_action( $action ) {

		if ( 1 == count( $args = func_get_args() ) ) {

			WP_Metadata::apply_class_filters( $this, $action, $args[ 1 ] );

		} else {

			array_unshift( $args, $this );
			call_user_func_array( array( 'WP_Metadata', 'apply_class_filters' ), $args );

		}

	}

	/**
	 * @param string $filter
	 * @param mixed $arg1
	 * @param mixed $arg2
	 * @param mixed $arg3
	 * @param mixed $arg4
	 * @param mixed $arg5
	 *
	 * @return mixed
	 */
	function apply_class_filters( $filter ) {

		if ( 2 == count( $args = func_get_args() ) ) {

			$result = WP_Metadata::apply_class_filters( $this, $filter, $args[ 1 ] );

		} else {

			array_unshift( $args, $this );
			$result = call_user_func_array( array( 'WP_Metadata', 'apply_class_filters' ), $args );

		}

		return $result;

	}

//	function _invoke_filter() {
//		$args = func_get_args();
//		list( $class_name, $filter ) = explode( '::', $current_filter = current_filter() );
//		$reflector = new ReflectionMethod( $class_name, $method_name = "_{$filter}" );
//		switch ( $reflector->getNumberOfParameters() ) {
//			case 0:
//				$result = $reflector->invoke( $this );
//				break;
//			case 1:
//				$result = $reflector->invoke( $this, $args[ 0 ] );
//				break;
//			default:
//				$result = $reflector->invokeArgs( $this, $args );
//				break;
//		}
//		return $result;
//	}

	/**
	 * @param $method_name
	 *
	 * @return bool
	 */
	function has_method( $method_name ) {
		return method_exists( $this, $method_name );
	}

	/**
	 * @param $property_name
	 *
	 * @return bool
	 */
	function has_property( $property_name ) {

		return property_exists( $this, $property_name ) || $this->has_class_var( $property_name );

	}

	/**
	 * @param string $property_name
	 *
	 * @return mixed|null
	 */
	function get_property( $property_name ) {

		if ( property_exists( $this, $property_name ) ) {
			$value = $this->{$property_name};

		} else {
			/**
			 * This will return null if class var does not exist.
			 */
			$value = $this->get_class_var( $property_name );

		}
		return $value;
	}

	/**
	 * @param string $property_name
	 * @param mixed $value
	 */
	function set_property( $property_name, $value ) {

		if ( is_object( $this ) ) {

			$this->{$property_name} = $value;

		}

	}

	/**
	 * @param $method_name
	 * @param array $args
	 *
	 * @return mixed
	 */
	function call_method( $method_name, $args = array() ) {

		return call_user_func_array( array( $this, $method_name ), $args );

	}

	/**
	 * @param string $property_name
	 * @param mixed $property_value
	 *
	 * @return mixed
	 */
	function maybe_auto_create( $property_name, $property_value ) {

		return $property_value;  // @todo Implement!

	}

	/**
	 * Assign the element values in the $args array to the properties of this object.
	 *
	 * @param array $args An array of name/value pairs that can be used to initialize an object's properties.
	 */
	function _assign_args( $args ) {

		foreach ( $args as $name => $value ) {

			$property_name = false;

			if ( $this->has_method( $method_name = "set_{$name}" ) ) {

				$this->call_method( $method_name, $value );

			} else if ( $this->has_property( $name ) ) {

				$property_name = $name;

			} else if ( $this->has_property( $non_public_name = "_{$name}" ) ) {

				$property_name = $non_public_name;

			} else {

				$this->custom_args[ $name ] = $value;

			}

			if ( $property_name ) {

				$this->{$property_name} = $this->maybe_auto_create( $name, $value );

			}

		}

	}

	/**
	 * @param string $property_name
	 *
	 * @return mixed|null
	 */
	function __get( $property_name ) {

		$value = null;

		if ( $this->has_property( $property_name ) ) {

			$value = $this->get_property( $property_name );

		}
		return $value;

	}

	/**
	 * @param array $args
	 * @param string $class_name
	 *
	 * @return WP_Object
	 */
	static function make_new( $args = array(), $class_name = null ) {

		if ( ! $class_name ) {
			$new = new static( $args );
		} else {
			$new = new $class_name( $args );
		}
		return $new;

	}

}
