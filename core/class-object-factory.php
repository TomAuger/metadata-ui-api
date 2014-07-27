<?php
/**
 * Class WP_Object_Factory
 */
class WP_Object_Factory {

  /**
   * @var string
   */
  var $factory_type;

  /**
   * @var callable
   */
  var $callable;

  /**
   * @var array
   */
  var $parameters;

  /**
   * @var array
   */
  var $extra = array();

  /**
   * @param string $factory_type
   * @param string[] $args
   */
  function __construct( $factory_type, $args ) {

    $this->factory_type = $factory_type;

    foreach( $args as $arg_name => $arg_value ) {
      if ( property_exists( $this, $arg_name ) && 'extra' != $arg_name ) {
        $this->$arg_name = $arg_value;
      } else {
        $this->extra[ $arg_name ] = $arg_value;
      }
    }

  }


}
