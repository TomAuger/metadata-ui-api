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
  private $_property_prefixes;

  /**
   * @var array
   */
  private $_default_args;

  /**
   * @var array
   */
  private static $_annotated_properties;


  /**
   * @return array
   */
  static function PROPERTIES() {

    return array();

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

    $args = wp_parse_args( $args, $this->default_args() );

    if ( $this->apply_class_filters( 'do_assign_args', $args ) ) {

      $args = $this->apply_class_filters( 'pre_prefix_args', $args );
      $args = $this->prefix_args( $args );

      $args = $this->apply_class_filters( 'pre_transform_args', $args );
      $args = $this->transform_args( $args );

      $args = $this->apply_class_filters( 'pre_collect_args', $args );
      $args = $this->collect_args( $args );

      $args = $this->apply_class_filters( 'reject_args', $args, null );

      $args       = $this->apply_class_filters( 'pre_assign_args', $args );
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
        $properties[ $property_name ] = $property->default;
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

    return $this->get_annotations( 'TRANSFORMS' );

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

            $args['transformed_args'][ $name ] = $value;
            unset( $args[ $name ] );

            $new_name = $result;
            if ( 1 <= ( $match_count = count( $matches ) - 1 ) ) {
              for ( $i = 1; $i <= $match_count; $i ++ ) {
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

    $args = WP_Metadata::collect_args( $args, array(
      'prefixes' => $this->get_property_prefixes(),
      'include' => 'all',
    ));

    return $args;

  }

  /**
   * @return string[]
   */
  function get_property_prefixes() {

    if ( ! isset( $this->_property_prefixes ) ) {

      $this->_property_prefixes = array();

      $annotated_properties = $this->get_annotated_properties( get_class( $this ) );

      foreach ( $annotated_properties as $field_name => $annotated_property ) {

        $this->_property_prefixes[ $field_name ] = $annotated_property->prefix ? $annotated_property->prefix : false;

      }

    }
    return $this->_property_prefixes;

  }

  /**
   * Assign the element values in the $args array to the properties of this object.
   *
   * @param array $args An array of name/value pairs that can be used to initialize an object's properties.
   */
  function assign_args( $args ) {

    $annotated_properties = $this->get_annotated_properties();

    $class_name = get_class( $this );

    $real_properties = get_class_vars( get_class( $this ) );

    $context = array(
      '$this' => $this,
      '$value' => null,
    );


    /*
     * Assign the arg values to properties, if they exist.
     * If no property exists capture value in the $this->extra[] array.
     */
    foreach ( $args as $name => $value ) {

      $property = false;

      /**
       * @var WP_Annotated_Property $property
       */
      if ( method_exists( $this, $method_name = "set_{$name}" ) ) {

        call_user_func( array( $this, $method_name ), $value );

      } else if ( isset( $annotated_properties[ $name ] ) ) {

        $property = $annotated_properties[ $name ];

        /**
         * @var WP_Annotated_Property $annotated_property
         */
        $annotated_property = $this->get_annotated_property( $name );

      } else if ( isset( $real_properties[ $name ] ) ) {

        $property = $real_properties[ $name ];

      } elseif ( WP_Metadata::non_public_property_exists( $class_name, $property_name = "_{$name}" ) ) {

        $this->extra_args[ $name ] = $value;

      }

      if ( $property ) {

        $property_name = $property->property_name;

        if ( isset( $annotated_property ) && $annotated_property->factory ) {

          $context['$value'] = $value;

          $factory = WP_Metadata::get_object_factory( $annotated_property->factory );

          if ( $annotated_property->prefix ) {

            $object_args = $factory->extract_args( $annotated_property->prefix, $args );

          } else {

            $object_args = array();

          }

          $value = $factory->make_object( $context, $object_args );

        }

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
   * @return WP_Annotated_Property[]|bool
   */
  function get_annotated_property( $property_name ) {

    $annotated_properties = $this->get_annotated_properties();

    return isset( $annotated_properties[ $property_name ] ) ? $annotated_properties[ $property_name ] : false;

  }

  /**
   * @return array
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

    call_user_func_array(
      array( 'WP_Metadata', 'apply_class_filters' ),
      array( $this, get_class( $this ), $filter, $value, array_slice( func_get_args(), 2 ) )
    );

  }

  /**
   * @param string $filter
   *
   */
  function do_class_action( $filter ) {

    call_user_func_array(
      array( 'WP_Metadata', 'do_class_action' ),
      array( $this, get_class( $this ), $filter, array_slice( func_get_args(), 1 ) )
    );

  }


}
