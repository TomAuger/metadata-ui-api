<?php

/**
 * Ensure that an $args array has an 'object_type' property of class WP_Object_Type
 *
 * Defaults to "post:{$post->post_type}"
 *
 * @param array $args
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
 * Get an array of class name lineage
 *
 * Returns an array of class names with most distant ancenstor first, current class last (if inclusive), or parent.
 *
 * @example array( 'WP_Base', 'WP_Field_Base', 'WP_Text_Field' )
 *
 * @todo Consider if there is a better name than 'lineage'?  Open to suggestion on GitHub issues...
 *
 * @param string $class_name
 * @param bool $inclusive
 * @return array
 */
function wp_get_class_lineage( $class_name, $inclusive = true ) {
  if ( ! ( $lineage = wp_cache_get( $cache_key = "class_lineage[{$class_name}]" ) ) ) {
    $lineage = $inclusive ? array( $class_name ) : array();
    if ( $class_name = get_parent_class( $class_name ) ) {
      $lineage = array_merge( wp_get_class_lineage( $class_name, true ), $lineage );
    }
    wp_cache_set( $cache_key, $lineage );
  }
  return $lineage;
}

/**
 * Returns an object type given a post type
 * @param string $post_type
 * @return string
 */
function wp_get_post_object_type( $post_type ) {
  return $post_type ? "post:{$post_type}" : 'post:all';
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
 * Registers a field for a user.
 *
 * @param string $field_name
 * @param bool|string $user_role
 * @param array $field_args
 */
function register_user_field( $field_name, $user_role = false, $field_args = array() ) {
  $object_type = $user_role ? "user:{$user_role}" : 'user:all';
  WP_Metadata::register_field( $field_name, $object_type, $field_args );
}

/**
 * Registers a field for a comment.
 *
 * Assumes we will eventually have comment types.
 *
 * @param string $field_name
 * @param bool|string $comment_type
 * @param array $field_args
 */
function register_comment_field( $field_name, $comment_type = false, $field_args = array() ) {
  $object_type = $comment_type ? "comment:{$comment_type}" : 'comment:all';
  WP_Metadata::register_field( $field_name, $object_type, $field_args );
}

/**
 * Registers a field for a global option.
 *
 * @param string $option_name
 * @param bool|string $option_group
 * @param array $field_args
 */
function register_option_field( $option_name, $option_group = false, $field_args = array() ) {
  $object_type = $option_group ? "option:{$option_group}" : 'option:all';
  WP_Metadata::register_field( $field_name, $object_type, $field_args );
}

/**
 * Registers a form for a post.
 *
 * @param string $form_name
 * @param bool|string $post_type
 * @param array $form_args
 */
function register_post_form( $form_name, $post_type = false, $form_args = array() ) {
  $object_type = wp_get_post_object_type( $post_type );
  WP_Metadata::register_form( $form_name, $object_type, $form_args );
}

/**
 * @param string $form_name
 * @param string $post_type
 * @return WP_Form
 */
function get_post_form( $form_name, $post_type, $form_args = array() ) {
  return WP_Metadata::get_form( $form_name, wp_get_post_object_type( $post_type ), $form_args );
}

