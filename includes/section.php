<?php

/**
 * Class WP_Section
 *
 * @mixin WP_Section_Data
 * @mixin WP_Section_Tags
 *
 */
class WP_Section extends WP_Item {

	/**
	 * @var string
	 */
	const ITEM_TYPE = 'section';

	/**
	 * @var string
	 */
	var $section_name;

	/**
	 * @param string $section_name
	 * @param array $section_args
	 */
	function __construct( $section_name, $section_args = array() ) {

		$this->section_name = $section_name;
		parent::__construct( $section_args );
	}

}

/**
 * Class WP_Section_Data
 *
 * @property WP_Panel $panel
 *
 */
class WP_Section_Data extends WP_Data {

	/**
	 * @var string
	 */
	var $section_label;

	/**
	 * @var WP_Panel
	 */
	var $panel;

	/**
	 * @var WP_Control[]
	 */
	var $controls = array();

	/**
	 * @var WP_Object_Type
	 */
	var $object_type;

	function __contruct( $args ) {

	 	parent::__construct( $args );

	}

	/**
	 * @param WP_Control $control
	 */
	function add_control( $control ) {

		$this->controls[ $control->control_name ] = $control;

		$control->section = $this;

	}

}
/**
 * Class WP_Section_Data
 * @method WP_Section_Data data()
 */
class WP_Section_Tags extends WP_Tags {

	/**
	 * @return string
	 */
	function get_html() {

		$html = array();
		foreach( $this->data()->controls as $control ) {

			$html[] = $control->get_html();

		}
		$html = implode( '', $html );

		return $html;
	}

}


