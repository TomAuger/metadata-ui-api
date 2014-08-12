<?php

/**
 * Class WP_Metadata_Base
 */
abstract class WP_Metadata_Base {

  /**
   * The property (var) prefix from a constant to be used for this current class.
   *
   * @example: const PREFIX = 'form';
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
   * @var bool
   */
  private $_initialized = array();

  /**
   * @var array
   */
  private $_default_args;

  /**
   * @var array
   */
  private static $_annotated_properties;

	/**
  * @var array
  */
	private static $_defaulted_property_values;


  /**
   * @return array
   */
  static function PROPERTIES() {

    return array();

  }

  /**
   * Defines the PARAMETERS for the static class factory method 'make_new'.
   *
   * @return array
   */
  static function PARAMETERS() {

    return array(
      '$args',
    );

  }

  /**
   * @return array
   */
  static function METHODS() {

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
   *
   */
  function __construct( $args = array() ) {

    if ( ! isset( $this->_initialized[ $this_class = get_class( $this ) ] ) ) {
      $this->_initialized[ $this_class ] = true;
      $this->do_class_action( 'initialize_class' );
    }

    if ( $this->apply_class_filters( 'do_assign_args', true, $args ) ) {

	    $default_args = $this->apply_class_filters( 'default_args', $this->default_args() );

	    $args = wp_parse_args( $args, $default_args );

      $args = $this->apply_class_filters( 'pre_prefix_args', $args );
      $args = $this->prefix_args( $args );

      $args = $this->apply_class_filters( 'pre_transform_args', $args );
      $args = $this->transform_args( $args );

      $args = $this->apply_class_filters( 'pre_collect_args', $args );
      $args = $this->collect_args( $args );

      $args = $this->apply_class_filters( 'pre_assign_args', $args );

      $this->args = $args;

      $this->assign_args( $args );

    }

    $this->do_class_action( 'initialize', $args );

  }

  /**
   * @return array
   */
  function default_args() {
    if ( ! isset( $this->_default_args ) ) {
      $properties   = $this->get_annotated_properties();
      foreach ( $properties as $property_name => $property ) {
        if ( is_null( $property->default ) || ! $property->auto_create ) {
	        unset( $properties[ $property_name ] );
        } else {
	        $properties[ $property_name ] = $property->default;
        }
      }
      $this->_default_args = $properties;
    }
    return $this->_default_args;
  }

  /**
   * Gets the property (var) prefix from a constant to be used for this current class.
   *
   * @example: const PREFIX = 'form';
   *
   * Intended to be used by subclasses.
   *
   * @return array
   */
  function get_prefix() {

    return $this->constant( 'PREFIX' );

  }

  /**
   * Returns an array of property transform regex as array key and expansion as key value.
   *
   * Subclasses should define TRANSFORMS() function:
   *
   *    return array(
   *      $regex1 => $transform1,
   *      $regex2 => $transform2,
   *      ...,
   *      $regexN => $transformN,
   *    );
   *
   * @note Multiple transforms can be applied so order is important.
   *
   * @return array
   */
  function get_transforms() {

    return $this->get_annotations( 'TRANSFORMS'  );

  }

  /**
   * @param string $const_name
   * @param bool|string $class_name
   *
   * @return mixed
   */
  function constant( $const_name, $class_name = false ) {

    if ( ! $class_name ) {

      $class_name = get_class( $this );

    }

    return WP_Metadata::constant( $class_name, $const_name );

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
          if ( preg_match( "#{$regex}#", $name, $matches ) ) {

            $args['_transformed_args'][ $name ] = $value;



            unset( $args[ $name ] );

            $new_name = $result;
            if ( 1 <= ( $top_index = count( $matches ) - 1 ) ) {
              for ( $i = 1; $i <= $top_index; $i++ ) {
                $new_name = str_replace( '$' . $i, $matches[ $i ], $new_name );
              }
            }
            $args[ $new_name ] = $value;
          }
        }
      }

    }

    return $args;

  }

  /**
   * Ensure all $args have been prefixed that don't already have an underscore in their name.
   *
   * @param array $args
   *
   * @return array
   */
  function prefix_args( $args ) {

    if ( $prefix = $this->get_prefix() ) {

      foreach ( $args as $arg_name => $arg_value ) {
        /**
         * For every $arg passed-in that does not contain an underscore, is not already prefixed, and
         * for which there is a property on this object
         */
        if ( false === strpos( $arg_name, '_' ) && ! preg_match( "#^{$prefix}_#", $arg_name ) &&
             (
                property_exists( $this, $property_name = "{$prefix}:{$arg_name}" ) ||
                method_exists( $this, $method_name = "set_{$property_name}" )
             )
        ) {

          $args[ $property_name ] = $arg_value;
          unset( $args[ $arg_name ] );

        }
      }
    }

    return $args;

  }

  /**
   * collect $args from delegate properties. Also store in $this->delegated_args array.
   *
   * @example
   *
   *  $input = array(
   *    'field_name' => 'Foo',
   *    'html:size' => 50,     // Will be split and "collect" like
   *    'wrapper:size' => 25,  // Assumes a TRANSFORMS() value that add's 'html' between 'wrapper' and 'size'
   *  );
   *  print_r( self::collect_args( $input ) );
   *  // Outputs:
   *  array(
   *    'field_name' => 'Foo',
   *    'html' => array( 'size' => 50 ),
   *    'wrapper' => array( 'html:size' => 25 ),
   *  );
   *
   *
   * @param array $args
   *
   * @return array
   */
  function collect_args( $args ) {

    $args = WP_Metadata::collect_args( $args, $this->get_property_prefixes() );

    return $args;

  }

  /**
   * @return string[]
   */
  function get_property_prefixes() {
	  /**
	   * @var string[] $property_prefixes
	   */
	  static $property_prefixes;

    if ( ! isset( $property_prefixes[ $class_name = get_class( $this ) ] ) ) {

      $property_prefixes = array();

      $annotated_properties = $this->get_annotated_properties( $class_name );

      foreach ( $annotated_properties as $field_name => $annotated_property ) {

        if ( $annotated_property->is_class() || $annotated_property->is_array() && ! empty( $annotated_property->prefix ) ) {

	        $property_prefixes[ $field_name ] = $annotated_property->prefix;

        }

      }

    }
    return $property_prefixes;

  }

	/**
	 * Return an array of annotated property names and their default values.
	 *
	 * @return array
	 */
	function get_defaulted_property_values() {

		if ( ! isset( self::$_defaulted_property_values[ $class_name = get_class( $this ) ] ) ) {

			$property_values = array();

			foreach( $this->get_annotated_properties() as $class_name => $property ) {

				if ( ! $property->auto_create ) {
					continue;
				}

				$property_name = $property->property_name;

				if ( is_null( $property->default ) && isset( $this->{$property_name} ) ) {

					$default_value = $this->{$property_name};

				} else {

					if ( 'array' == $property->property_type && isset( $property->keys ) ) {

						$default_value = array_fill_keys( $property->keys, $property->default );

					} else {

						$default_value = $property->default;

					}

				}

				$property_values[$property_name] = $default_value;

			}

			self::$_defaulted_property_values = $property_values;

		}

		return self::$_defaulted_property_values;
	}

  /**
   * Assign the element values in the $args array to the properties of this object.
   *
   * @param array $args An array of name/value pairs that can be used to initialize an object's properties.
   */
  function assign_args( $args ) {

    $class_name = get_class( $this );

		$args = array_merge( $this->get_defaulted_property_values(), $args );

    /*
     * Assign the arg values to properties, if they exist.
     * If no property exists capture value in the $this->extra[] array.
     */
    foreach ( $args as $name => $value ) {

      $property = $property_name = false;

      /**
       * @var WP_Annotated_Property $property
       */
      if (  '$' == $name[0] || preg_match( '#^_(transformed|collected)_args$#', $name ) ) {

	      continue;

      } else if ( method_exists( $this, $method_name = "set_{$name}" ) ) {

        call_user_func( array( $this, $method_name ), $value );

      } else if ( $this->has_annotated_property( $name ) ) {

        $annotated_property = $this->get_annotated_property( $property_name = $name );

				if ( $annotated_property->auto_create ) {

					if ( $annotated_property->is_class() ) {

						$object_args = $this->extract_prefixed_args( $annotated_property->prefix, $args );

						$object_args['$value'] = $value;
					  $object_args['$parent'] = $this;

				    $value = $annotated_property->make_object( $object_args );

	        } else if ( $annotated_property->is_array()  ) {

	          if ( ! empty( $value ) ) {

		          $parent_class_name = $annotated_property->array_of;

							if ( is_array( $annotated_property->keys )
								&& ! empty( $annotated_property->registry )
								&& WP_Metadata::registry_exists( $annotated_property->registry ) ) {

			          foreach( $annotated_property->keys as $key_name ) {

				          $object_args = isset( $value[$key_name] ) ? $value[$key_name] : array();

				          $object_args['$value'] = $key_name;
				          $object_args['$parent'] = $this;

									$class_name = WP_Metadata::get_registry_item( $annotated_property->registry, $key_name );

									if ( ! is_subclass_of( $class_name, $parent_class_name ) ) {

										$error_msg = __( 'ERROR: No registered class %s in registry %s.', 'wp-metadata' );
										trigger_error( sprintf( $error_msg, $key_name, $annotated_property->registry ) );

									} else {


					          $parameters = WP_Metadata::build_property_parameters( $class_name, $object_args );

		                $value[$key_name] = call_user_func_array( array( $class_name, 'make_new' ), $parameters );

									}

			          }

							}

	          }

	        }

				}

      } else if ( property_exists( $this, $name ) ) {

        $property_name = $name;

      } else if ( property_exists( $this, $non_public_name = "_{$name}" ) ) {

	      $property_name = $non_public_name;

      } else {

        $this->extra_args[ $name ] = $value;

      }

      if ( $property_name ) {

        $this->{$property_name} = $value;

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

    } else {

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

      if ( method_exists( $this, $method_name = $match[1] ) ) {

        $method_exists = true;

      } elseif ( method_exists( $this, $method_name = "{$method_name}_html" ) ) {

        $method_exists = true;

      } elseif ( method_exists( $this, $method_name = "get_{$method_name}" ) ) {

        $method_exists = true;

      } elseif ( method_exists( $this, $method_name = "get_{$match[1]}" ) ) {

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
   * Gets array of properties field names that should not get a prefix.
   *
   * @param string $property_name
   *
   * @return WP_Annotated_Property|bool
   */
  function get_annotated_property( $property_name ) {

    $annotated_properties = $this->get_annotated_properties();

    return isset( $annotated_properties[ $property_name ] ) ? $annotated_properties[ $property_name ] : false;

  }

  /**
   * @param string $property_name
   * @return bool
   */
  function has_annotated_property( $property_name ) {

	  return isset( self::$_annotated_properties[ get_class( $this ) ][ $property_name ] );

  }

  /**
   * @return WP_Annotated_Property[]
   */
  function get_annotated_properties() {

    if ( ! isset( self::$_annotated_properties[ $class_name = get_class( $this ) ] ) ) {

      /**
       * @var array[] $annotated_properties
       */
      $annotated_properties = $this->get_annotations( 'PROPERTIES' );

      foreach ( $annotated_properties as $property_name => $property_args ) {

        $annotated_properties[ $property_name ] = new WP_Annotated_Property( $property_name, $property_args );

      }

      self::$_annotated_properties[ $class_name ] = $annotated_properties;

    }
    return self::$_annotated_properties[ $class_name ];

  }

  /**
   * @return array
   */
  function get_properties() {

    $annotated_properties = $this->get_annotated_properties();

    $real_properties = get_class_vars( get_class( $this ) );

    return array_merge( $annotated_properties, $real_properties );

  }

  /**
   * @return array
   */
  function get_annotated_property_names() {

    return array_keys( $this->get_annotated_properties() );

  }

  /**
   * @param string $annotation_name
   * @param array $annotations
   *
   * @return array
   */
  function get_annotations( $annotation_name, $annotations = array() ) {

    return WP_Metadata::get_annotations( $this, $annotation_name, $annotations );

  }

  /**
   * @param string $filter
   * @param mixed $value
   *
   * @return mixed
   */
  function apply_class_filters( $filter, $value ) {

    return call_user_func_array(
      array( 'WP_Metadata', 'apply_class_filters' ),
      array( $this, get_class( $this ), $filter, $value, array_slice( func_get_args(), 2 ) )
    );

  }

  /**
   * @param string $filter
   *
   */
  function do_class_action( $filter ) {

    $args = func_get_args();

    $call_args = array_merge(
      array( $this, get_class( $this ), $filter ),
      array_slice( $args, 1 )
    );

    call_user_func_array( array( 'WP_Metadata', 'do_class_action' ), $call_args );

  }

  /**
   * @param string $prefix
   * @param array $args
   * @return mixed|array;
   */
  function extract_prefixed_args( $prefix, $args ) {

	  if ( ! $prefix || empty( $args[$prefix] ) || ! is_array( $prefixed_args = $args[$prefix] ) ) {

			  $prefixed_args = array();

		}

    return $prefixed_args;

  }

}
