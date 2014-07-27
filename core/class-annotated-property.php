<?php

/**
 * Class WP_Annotated_Property
 */
class WP_Annotated_Property {

  /**
   * @var string
   */
  var $property_name;

  /**
   * @var string
   * @example
   *    'Class_Name', 'Class_Name[]', etc.
   */
  var $property_type;

  /**
   * @var string
   * @example
   *    'Class_Name', 'int', etc.
   */
  var $array_of = null;

  /**
   * @var string
   * @example
   *    array( 'WP_Metadata', 'make_field' ), 'make
   */
  var $factory;

  /**
   * @var mixed
   */
  var $default;

  /**
   * @var array
   */
  var $parameters = array();

  /**
   * @var string
   * @example
   *    'form', 'field', 'storage', etc.
   */
  var $prefix;

  /**
   * @var array
   */
  var $extra = array();

  /**
   * @param string $property_name
   * @param string[] $args
   */
  function __construct( $property_name, $args ) {
    $args = wp_parse_args( $args );

    if ( empty( $args['type'] ) ) {
      $this->property_type = 'string';
    } else if ( preg_match( '#(.+)\[\]$#', $args['type'], $match ) ) {
        $this->property_type = 'array';
        $this->array_of = $match[1];
    } else {
      $this->property_type = $args['type'];
    }
    unset( $args['type'] );

    $this->property_name = $property_name;

    foreach( $args as $arg_name => $arg_value ) {
      if ( property_exists( $this, $arg_name ) && 'extra' != $arg_name ) {
        $this->$arg_name = $arg_value;
      } else {
        $this->extra[ $arg_name ] = $arg_value;
      }
    }

  }

  /**
   * @return bool
   */
  function is_class() {
    return class_exists( $this->property_type );
  }

}
