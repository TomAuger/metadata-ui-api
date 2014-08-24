<?php
/**
 * Class WP_Textarea_Field_View
 */
class WP_Textarea_Field_View extends WP_Field_View_Base {

	/**
	 */
	static function CLASS_VARS() {
		return array(
			'defaults' => array( 'features[input]:element:html_tag' => 'textarea' ),
		);
	}

}
