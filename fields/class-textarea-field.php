<?php
/**
 * Class WP_Textarea_Field
 */
class WP_Textarea_Field extends WP_Field_Base {

	/**
	 *
	 */
	const FIELD_TYPE = 'textarea';


	/**
	 * @todo VERIFY THIS IS THE RIGHT APPROACH FOR SETTING DEFAULTS
	 *
	 * @param array $args
	 * @return array
	 */
	function default_args( $args ) {

	 	$args[ 'view:view_type' ] = 'textarea';
		return $args;

	}

}
