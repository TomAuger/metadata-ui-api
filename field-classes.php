<?php
/**
 * Field Classes
 */

/**
 * Class WP_Text_Field
 */
class WP_Text_Field extends WP_Field_Base {

	const FIELD_TYPE = 'text';

}

/**
 * Class WP_Textarea_Field
 */
class WP_Textarea_Field extends WP_Field_Base {

	/**
	 *
	 */
	const FIELD_TYPE = 'textarea';

	/**
	 * @return array
	 */
	static function CLASS_VALUES() {

		return array(
				'defaults' => array( 'view:view_type' => 'textarea' ),
		);

	}

}

/**
 * Class WP_Url_Field
 */
class WP_Url_Field extends WP_Field_Base {

	/**
	 *
	 */
	const FIELD_TYPE = 'url';

}

/**
 * Class WP_Date_Field
 */
class WP_Date_Field extends WP_Field_Base {

	const FIELD_TYPE = 'date';

}

/**
 * Class WP_Editor_Field
 */
class WP_Editor_Field extends WP_Field_Base {

	/**
	 *
	 */
	const FIELD_TYPE = 'editor';

}

/**
 * Class WP_Hidden_Field
 */
class WP_Hidden_Field extends WP_Field_Base {

	/**
	 *
	 */
	const FIELD_TYPE = 'hidden';

	/**
	 * @var bool If true add '[]' to $this->view->element_name()
	 */
	var $shared_name = false;

}

