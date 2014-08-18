<?php
/**
 * Class WP_Textarea_Field_View
 */
class WP_Textarea_Field_View extends WP_Field_View_Base {

	/**
	 * @todo VERIFY THIS IS THE RIGHT APPROACH FOR SETTING DEFAULTS
	 *
	 * @param array $args
	 * @return array|void
	 */
	function default_args( $args ) {
	 	$args['features[input]:element:html_tag'] ='textarea';
		return $args;
	}

}
