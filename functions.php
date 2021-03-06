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
 * Register a new Object Type $class.
 *
 * Allows a plugin or theme to register it' own $class values for Object Types.
 *
 * An example might be for a plugin we call 'Awesome Event Calendar', it might
 * register a new Object Type $class of 'aec_event' where 'aec_' is the plugin's
 * prefix:
 *
 *    register_object_type_class( 'aec_event' );
 *
 * This would allow developers to register fields for an 'aec_event'.
 * HOWEVER, an event would probably best be a custom post type so this functionality
 * may be rarely used, if ever.  Still, it's here if it is needed.
 *
 * The $args array is currently unused but here for future needs.
 *
 * $class values cannot be registered twice
 *
 * @param string $class The new Object Type $class to register.
 * @param array $class_args The $args for the registered $class. Currently unused.
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

////////////////////////////////////////////////////////
//////[User Functions]//////////////////////////////////
////////////////////////////////////////////////////////

/**
 * Returns an object type given a user role
 *
 * @param string $user_role
 *
 * @return string
 */
function wp_get_user_object_type( $user_role ) {

	return $user_role ? "user:{$user_role}" : 'user:any';

}

/**
 * Registers a form for a user.
 *
 * @param string $form_name
 * @param bool|string $user_role
 * @param array $field_args
 */
function register_user_form( $form_name, $user_role = false, $form_args = array() ) {

	WP_Metadata::register_form( $form_name, wp_get_user_object_type( $user_role ), $form_args );

}

/**
 * Registers a field for a user.
 *
 * @param string $field_name
 * @param bool|string $user_role
 * @param array $field_args
 */
function register_user_field( $field_name, $user_role = false, $field_args = array() ) {

	WP_Metadata::register_field( $field_name, wp_get_user_object_type( $user_role ), $field_args );

}

/**
 * @param string $form_name
 * @param string $user_role
 * @param array $form_args
 *
 * @return WP_Form
 */
function get_user_form( $form_name, $user_role, $form_args = array() ) {

	return WP_Metadata::get_form( $form_name, wp_get_user_object_type( $user_role ), $form_args );

}

////////////////////////////////////////////////////////
//////[Post Functions]//////////////////////////////////
////////////////////////////////////////////////////////

/**
 * Registers a form for a post.
 *
 * @param string $form_name
 * @param bool|string $post_type
 * @param array $form_args
 */
function register_post_form( $form_name, $post_type = false, $form_args = array() ) {

	WP_Metadata::register_form( $form_name, WP_Metadata::get_post_object_type_literal( $post_type ), $form_args );

}

/**
 * Registers a field for a post.
 *
 * @param string $field_name
 * @param bool|string $post_type
 * @param array $field_args
 */
function register_post_field( $field_name, $post_type = false, $field_args = array() ) {

	WP_Metadata::register_field( $field_name, WP_Metadata::get_post_object_type_literal( $post_type ), $field_args );

}

/**
 * @param string $form_name
 * @param string $post_type
 * @param array $form_args
 *
 * @return WP_Form
 */
function get_post_form( $form_name, $post_type, $form_args = array() ) {

	return WP_Metadata::get_form( $form_name, WP_Metadata::get_post_object_type_literal( $post_type ), $form_args );

}

/**
 * @param string $post_type
 * @param bool|array $form_names
 *
 * @return array
 */
function get_post_forms( $post_type, $form_names = false ) {

	return WP_Metadata::get_forms( WP_Metadata::get_post_object_type_literal( $post_type ), $form_names );

}

////////////////////////////////////////////////////////
//////[Option Functions]////////////////////////////////
////////////////////////////////////////////////////////

/**
 * Registers a form for a option.
 *
 * @param string $form_name
 * @param string $option_group
 * @param array $form_args
 */
function register_option_form( $form_name, $option_group, $form_args = array() ) {

	WP_Metadata::register_form( $form_name, "option:{$option_group}", $form_args );

}

/**
 * Registers a field for a option.
 *
 * @param string $field_name
 * @param string $option_group
 * @param array $field_args
 */
function register_option_field( $field_name, $option_group, $field_args = array() ) {

	WP_Metadata::register_field( $field_name, "option:{$option_group}", $field_args );

}

/**
 * @param string $form_name
 * @param string $option_group
 * @param string $form_args
 *
 * @return WP_Form
 */
function get_option_form( $form_name, $option_group, $form_args = array() ) {

	return WP_Metadata::get_form( $form_name, "option:{$option_group}", $form_args );

}

////////////////////////////////////////////////////////
//////[Comment Functions]///////////////////////////////
////////////////////////////////////////////////////////

/**
 * Returns an object type given a comment type
 *
 * @param string $comment_type
 *
 * @return string
 */
function wp_get_comment_object_type( $comment_type ) {

	return $comment_type ? "comment:{$comment_type}" : 'comment:any';

}

/**
 * Registers a form for a comment.
 *
 * @param string $form_name
 * @param bool|string $comment_type
 * @param array $form_args
 */
function register_comment_form( $form_name, $comment_type = false, $form_args = array() ) {

	WP_Metadata::register_form( $form_name, wp_get_comment_object_type( $comment_type ), $form_args );

}

/**
 * Registers a field for a comment.
 *
 * @param string $field_name
 * @param bool|string $comment_type
 * @param array $field_args
 */
function register_comment_field( $field_name, $comment_type = false, $field_args = array() ) {

	WP_Metadata::register_field( $field_name, wp_get_comment_object_type( $comment_type ), $field_args );

}

/**
 * @param string $form_name
 * @param string $comment_type
 * @param array $form_args
 *
 * @return WP_Form
 */
function get_comment_form( $form_name, $comment_type, $form_args = array() ) {

	return WP_Metadata::get_form( $form_name, wp_get_comment_object_type( $comment_type ), $form_args );

}
