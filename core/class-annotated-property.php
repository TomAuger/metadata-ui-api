<?php

/**
 * Class WP_Annotated_Property
 *
 */
final class WP_Annotated_Property {

	/**
	 * @var array List of default
	 */
	static $_default_annotations = array();

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
	 * @param string[] $annotations
	 */
	function __construct( $property_name, $annotations ) {

		$this->property_name = $property_name;

		if ( empty( $annotations['type'] ) ) {
			$this->property_type = 'string';
		} else if ( preg_match( '#(.+)\[\]$#', $annotations['type'], $match ) ) {
			$this->property_type = 'array';
			$this->array_of      = $match[1];
		} else {
			$this->property_type = $annotations['type'];
		}
		unset( $annotations['type'] );

		/**
		 * Default 'prefix' and 'factory' to property name, for convenience.
		 */
		if ( class_exists( $this->property_type )
		     || ( $this->is_array() && class_exists( $this->array_of ) )
		) {

			if ( ! isset( $annotations['prefix'] ) ) {
				$this->prefix = $property_name;
			}

			$annotations = array_merge(
					self::get_default_annotations( $this->property_type ),
					$annotations
			);

		}

		foreach ( $annotations as $arg_name => $arg_value ) {
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
	 * Get default annotations for a 'type?'
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	static function get_default_annotations( $type ) {

		if ( ! isset( self::$_default_annotations[ $type ] ) ) {

			self::$_default_annotations[ $type ] = (object) array(
					'cached' => false,
					'values' => array(),
			);

		}

		if ( ! self::$_default_annotations[ $type ]->cached ) {

			if ( $parent = get_parent_class( $type ) ) {

				self::$_default_annotations[ $type ]->values = array_merge(
						self::get_default_annotations( $parent ),
						self::$_default_annotations[ $type ]->values
				);

			}

			self::$_default_annotations[ $type ]->cached = true;

		}

		return self::$_default_annotations[ $type ]->values;

	}

	/**
	 * Register the default annotations for a 'type'. In the case of type=class_name, also it's child classes.
	 *
	 * @param string $type
	 * @param array $default_values
	 */
	static function register_default_annotations( $type, $default_values ) {

		self::$_default_annotations[ $type ] = (object) array(
				'cached' => false,
				'values' => $default_values,
		);

	}

	/**
	 * @param string $annotation_name
	 *
	 * @return null
	 */
	function get_annotation_value( $annotation_name ) {

		if ( property_exists( $this, $annotation_name ) ) {

			$annotation = $this->{$annotation_name};

		} else if ( property_exists( $this, $long_name = "property_{$annotation_name}" ) ) {

			$annotation = $this->{$long_name};

		} else if ( isset( $this->extra[ $annotation_name ] ) ) {

			$annotation = $this->extra[ $annotation_name ];

		} else {

			$annotation = null;

		}

		return $annotation;

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
	 * @return bool
	 */
	function is_class() {

		return class_exists( $this->property_type );

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

		$make_new_parameters = WP_Metadata::get_make_new_parameters( $class_name );

		foreach ( $make_new_parameters as $parameter_name ) {

			if ( preg_match( '#^(\$value|\$parent)$#', $parameter_name ) ) {

				$parameters[] = $object_args[ $parameter_name ];

			} else if ( '$args' == $parameter_name ) {

				$args_index   = count( $parameters );
				$parameters[] = $object_args;

			} else if ( is_null( $parameter_name ) || is_bool( $parameter_name ) ) {

				$parameters[] = $parameter_name;

			} else if ( isset( $object_args[ $property_key = ltrim( $parameter_name, '$' ) ] ) ) {

				$parameters[] = $object_args[ $property_key ];

			} else {

				$parameter_count = count( $parameters );

				/**
				 * Allow for user defined values in WP_Annotated_Property
				 *
				 * @var array $annotation_args
				 */
				$property_args = $object_args['$property']->extra;

				if ( isset( $property_args[ $property_key = ltrim( $parameter_name, '$' ) ] ) ) {

					$parameters[] = $property_args[ $property_key ];

				}

				if ( $parameter_count == count( $parameters ) ) {

					trigger_error( 'Inside WP_Annotated_Property::build_parameters(); assumption failed.' );

				}

			}

		}

		if ( $args_index ) {

			foreach ( array_keys( $parameters[ $args_index ] ) as $key_name ) {
				if ( '$' == $key_name[0] ) {
					unset( $parameters[ $args_index ][ $key_name ] );
				}
			}

		}

		return $parameters;

	}

}
