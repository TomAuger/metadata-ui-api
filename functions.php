<?php
/**
 * Ensure that an $args array has an 'object_type' property of class WP_Object_Type
 *
 * Defaults to "post:{$post->post_type}"
 *
 * @param array $args
 *
 * @return array
 */
function wp_ensure_object_type( $args ) {

	$args = wp_parse_args( $args );

	if ( empty( $args['object_type'] ) ) {
		global $post;

		$args['object_type'] = isset( $post->post_type ) ? $post->post_type : false;
	}

	if ( ! $args['object_type'] instanceof WP_Object_Type ) {
		$args['object_type'] = new WP_Object_Type( $args['object_type'] );
	}

	return $args;

}

/**
 * Register object type
 *
 * @param $class
 * @param $class_args
 *
 * @return bool Whether the object type $class was registered
 */
function register_object_type_class( $class, $class_args = array() ) {
	return WP_Object_Type::register_class( $class, $class_args );
}

/**
 * Register field type
 *
 * @param string $type_name
 * @param string|array $type_def - Classname, or array of $args
 *
 * @return bool Whether the object type $type_name was registered
 */
function register_field_type( $type_name, $type_def = array() ) {
	return WP_Metadata::register_field_type( $type_name, $type_def );
}

/**
 * @param string $class_name
 * @param string $class_filepath
 *
 * @return bool Return true if it was registered, false if not.
 */
function register_autoload_class( $class_name, $class_filepath ) {
	return WP_Metadata::register_autoload_class( $class_name, $class_filepath );
}



