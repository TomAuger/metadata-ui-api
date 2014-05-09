<?php
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