<?php
/**
 * Class WP_Field_Infobox_Feature
 */
class WP_Field_Infobox_Feature extends WP_Field_Feature_Base {

	var $infobox_text;

	/**
	 * @return string
	 */
	function html_value() {

		return $this->infobox_text;

	}

}
