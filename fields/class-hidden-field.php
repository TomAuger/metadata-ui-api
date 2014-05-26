<?php
/**
 * Class WP_Hidden_Field
 */
class WP_Hidden_Field extends WP_Field_Base {

	/**
	 *
	 */
	const HTML_TYPE = 'hidden';

	/**
	 *
	 */
	const FIELD_TYPE = 'hidden';

	/**
	 * @var bool If true add '[]' to $this->view->html_name()
	 */
	var $shared_name = false;

}