<?php
/**
 * Returns an object type given a comment type
 *
 * @param string $comment_type
 *
 * @return string
 */
function wp_get_comment_object_type( $comment_type ) {

	return $comment_type ? "comment:{$comment_type}" : 'comment:all';

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