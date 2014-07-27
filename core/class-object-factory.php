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

  /**
   * @param array $context
   * @param array $args
   *
   * @return object
   */
  function make_object( $context, $args ) {

    $parameters = $this->build_parameters( $context, $args );

    $object = call_user_func_array( $this->callable, $parameters );

    return $object;

  }


  /**
   * Build Parameters for Object Constructor
   *
   * @param array $context
   * @param array $args
   *
   * @return array
   */
  function build_parameters( $context, $args ) {

    $parameters = array();

    foreach( $this->parameters as $parameter_name ) {

      if ( preg_match( '#^(\$this|\$value)$#', $parameter_name ) ) {
        $parameters[] = $context[$parameter_name];

      } else if ( '$args' == $parameter_name ) {
        $parameters[] = $args;

      } else if ( property_exists( $this, $parameter_name ) ) {
        $parameters[] = $this->$parameter_name;

      } else if ( isset( $args[ $parameter_name ] ) ) {
        $parameters[] = $args[ $parameter_name ];

      }

    }

    return $parameters;

  }

  /**
   * @param string $prefix
   * @param array $args
   * @return mixed|array;
   */
  function extract_args( $prefix, $args ) {

    return ! empty( $args[$prefix] ) ? $args[$prefix] : array();

  }

}
