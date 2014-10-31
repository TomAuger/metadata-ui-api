<?php

/**
 * Class WP_Text_Field_View
 */
class WP_Text_Field_View extends WP_Field_View_Base {

}
/**
 * Class WP_Textarea_Field_View
 */
class WP_Textarea_Field_View extends WP_Field_View_Base {

	/**
	 */
	static function CLASS_VALUES() {
		return array(
				'defaults' => array( 'features[input]:element:html_tag' => 'textarea' ),
		);
	}

}

/**
 * Class WP_Select_Field_View
 */
class WP_Select_Field_View extends WP_Field_View_Base {

}

/**
 * Class WP_Hidden_Field_View
 *
 * @property WP_Hidden_Field $field
 */
class WP_Hidden_Field_View extends WP_Field_View_Base {

}
