<?php
/**
 * Returns an object type given a post type
 *
 * @param string $post_type
 *
 * @return string
 */
function wp_get_post_object_type( $post_type ) {

	return $post_type ? "post:{$post_type}" : 'post:any';

}

/**
 * Registers a form for a post.
 *
 * @param string $form_name
 * @param bool|string $post_type
 * @param array $form_args
 */
function register_post_form( $form_name, $post_type = false, $form_args = array() ) {

	WP_Metadata::register_form( $form_name, wp_get_post_object_type( $post_type ), $form_args );

}

/**
 * Registers a field for a post.
 *
 * @param string $field_name
 * @param bool|string $post_type
 * @param array $field_args
 */
function register_post_field( $field_name, $post_type = false, $field_args = array() ) {

	WP_Metadata::register_field( $field_name, wp_get_post_object_type( $post_type ), $field_args );

}

/**
 * @param string $form_name
 * @param string $post_type
 * @param array $form_args
 *
 * @return WP_Form
 */
function get_post_form( $form_name, $post_type, $form_args = array() ) {

	return WP_Metadata::get_form( $form_name, wp_get_post_object_type( $post_type ), $form_args );

}

/**
 * @param string $post_type
 * @param bool|array $form_names
 *
 * @return array
 */
function get_post_forms( $post_type, $form_names = false ) {

	return WP_Metadata::get_forms( wp_get_post_object_type( $post_type ), $form_names );

}
