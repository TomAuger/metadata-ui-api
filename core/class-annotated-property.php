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
  * @var string
  * @example
  *    'field_type', 'storage_type', 'field_feature_type', etc.
  */
  var $registry;

	/**
	 * @var bool
	 */
	var $auto_create = true;

	/**
  * @var array
  */
  var $extra = array();

	/**
  * @var array
  */
  var $keys = array();

  /**
   * @param string $property_name
   * @param string[] $args
   */
  function __construct( $property_name, $args ) {
    $args = wp_parse_args( $args );

	  $this->property_name = $property_name;

    if ( empty( $args['type'] ) ) {
      $this->property_type = 'string';
    } else if ( preg_match( '#(.+)\[\]$#', $args['type'], $match ) ) {
        $this->property_type = 'array';
        $this->array_of = $match[1];
    } else {
      $this->property_type = $args['type'];
    }
    unset( $args['type'] );

	  /**
	   * Default 'prefix' and 'factory' to property name, for convenience.
	   */
	  if ( class_exists( $this->property_type )
	    || ( $this->is_array() && class_exists( $this->array_of ) ) ) {
			if ( ! isset( $args[ 'prefix' ] ) ) {
				$this->prefix  = $property_name;
			}
		}

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
 function is_array() {

   return 'array' == $this->property_type;

 }

	/**
  * @return bool
  */
 function is_class() {

   return class_exists( $this->property_type );

 }


	/**
  * @param array $object_args
  *
  * @return object
  */
 function make_object( $object_args ) {

   if ( $this->is_class() && method_exists( $this->property_type, 'make_new' ) ) {

	   $parameters = self::build_parameters( $this->property_type, $object_args );

     $object = call_user_func_array( array( $this->property_type, 'make_new' ), $parameters );

   } else {

     $object = $object_args;

   }

   return $object;

 }

	/**
  * Build Parameters for Object Constructor
  *
  * @param string $class_name
  * @param array $object_args
  *
  * @return array
  */
 static function build_parameters( $class_name, $object_args ) {

   $parameters = array();

   $args_index = false;

   foreach( WP_Metadata::get_make_new_parameters( $class_name ) as $parameter_name ) {

     if ( preg_match( '#^(\$value|\$parent)$#', $parameter_name ) ) {

       $parameters[] = $object_args[ $parameter_name ];

     } else if ( '$args' == $parameter_name ) {

	     $args_index = count( $parameters );
       $parameters[] = $object_args;

     } else {

	    trigger_error( 'Inside WP_Annotated_Property::build_parameters(); assumption failed.' );

      if ( property_exists( $class_name, $parameter_name ) ) {
        $parameters[] = $object_args[ '$parent' ]->{$parameter_name};

      } else if ( isset( $object_args[ $parameter_name ] ) ) {
        $parameters[] = $object_args[ $parameter_name ];

      } else {
	      $parameters[] = null;
      }

     }

   }

	 if ( $args_index ) {

     foreach( array_keys( $parameters[ $args_index ] ) as $key_name )
	      if ( '$' == $key_name[0] ) {
	       unset( $parameters[ $args_index ][ $key_name ] );
	      }

   }

   return $parameters;

 }


}
