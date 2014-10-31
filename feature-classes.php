<?php
/**
 * Field Feature Classes
 */

/**
 * Class WP_Label_Feature
 */
class WP_Label_Feature extends WP_Feature_Base {

	const FEATURE_TYPE = 'label';

	/**
	 * @var string
	 */
	var $label_text;

	/**
	 * @return array
	 */
	static function PROPERTIES() {

		return array(

				'element' => array( 'html_tag' => 'label' ),

		);

	}

	/**
	 * @return mixed|string
	 */
	function get_element_value() {

		return $this->label_text;

	}

}

/**
 * Class WP_Input_Feature
 */
class WP_Input_Feature extends WP_Feature_Base {

	const FEATURE_TYPE = 'input';

	/**
	 * @return array
	 */
	static function PROPERTIES() {

		return array(
				'element' => array( 'html_tag' => 'input' ),
		);

	}

	/**
	 * @return mixed
	 */
	function get_element_value() {

		return $this->field->value();

	}

	/**
	 * @note This is done in pre_initialize because element_id() might be calculated based on element_name()
	 *       and element_id() is calculated in WP_View_Base::initialize_attribute().
	 *
	 * @param array $input_args
	 *
	 * @return array
	 */
	function pre_initialize( $input_args ) {

		$this->element->set_name( $this->initialize_attribute( 'name', 'element' ) );

		return $input_args;

	}

}

/**
 * Class WP_Help_Feature
 */
class WP_Help_Feature extends WP_Feature_Base {

	const FEATURE_TYPE = 'help';

	var $help_text;

	/**
	 * @return string
	 */
	function get_element_value() {

		return $this->help_text;

	}

}

/**
 * Class WP_Message_Feature
 */
class WP_Message_Feature extends WP_Feature_Base {

	const FEATURE_TYPE = 'message';

	var $message_text;

	/**
	 * @return string
	 */
	function get_element_value() {

		return $this->message_text;

	}

}

/**
 * Class WP_Infobox_Feature
 */
class WP_Infobox_Feature extends WP_Feature_Base {

	const FEATURE_TYPE = 'infobox';

	var $infobox_text;

	/**
	 * @return string
	 */
	function get_element_value() {

		return $this->infobox_text;

	}

}
