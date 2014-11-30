<?php

/**
 * Class WP_Panel
 *
 * @mixin WP_Panel_Data
 * @mixin WP_Panel_Tags
 *
 * @method void the_html()
 */
class WP_Panel extends WP_Section {

	const ITEM_TYPE = 'panel';

	/**
	 * @var string
	 */
	var $panel_name;

	/**
	 * @param string $panel_name
	 * @param WP_Object_Type $object_type
	 * @param array $panel_args
	 */
	function __construct( $panel_name, $object_type, $panel_args = array() ) {

		$panel_args = wp_parse_args( $panel_args, array(
			'object_type' => 'post:post',
		));
		$this->panel_name = $panel_name;
		$panel_args[ 'object_type' ] = new WP_Object_Type( $panel_args[ 'object_type' ] );
		parent::__construct( $panel_args );

	}

}

/**
 * Class WP_Panel_Data
 */
class WP_Panel_Data extends WP_Section_Data {

	/**
	 * @var WP_Section[]
	 */
	var $sections = array();

	/**
	 * @param WP_Section $section
	 */
	function add_section( $section ) {

		$this->sections[ $section->section_name ] = $section;
		$section->panel = $this;
		$section->object_type = $this->object_type;

	}

}

/**
 * Class WP_Panel_Tags
 *
 * @method WP_Panel_Data data()
 */
class WP_Panel_Tags extends WP_Section_Tags {

	/**
	 * @return WP_Panel
	 */
	function as_panel() {
		$this->item;
	}

	function get_html() {

		$panel_html = array();
		foreach( $this->data()->sections as $section ) {

			$panel_html[] = $section->get_html();

		}
		$panel_html = implode( '', $panel_html );

		return $panel_html;

	}


}
