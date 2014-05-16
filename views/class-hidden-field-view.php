<?php
/**
 * Class WP_Hidden_Field_View
 *
 * @property WP_Hidden_Field $field
 */
class WP_Hidden_Field_View extends WP_Field_View_Base {

	/**
	 * @param string $view_name
	 * @param array $view_args
	 */
	function __construct( $view_name, $view_args = array() ) {
		parent::__construct( $view_name, $view_args );
	}


	/**
	 * CONSTANT method that maps $arg prefixes to delegated properties
	 *
	 * @return array
	 */
	static function DELEGATES() {

		return array( 'input' => 'input' );

	}

	/**
	 * @return bool|string
	 */
	function html_id() {

		if ( $this->field->shared_name ) {

			/*
			 * If $shared_name is true there will be '[]' contained in the html ID from $this->html_name().
			 * Remove it.
			 */
			return preg_replace( '#\[\]#', '', parent::html_id() );

		}

		return  parent::html_id();
	}

	/**
	 * @return bool|string
	 */
	function html_name() {

		if ( $this->field->shared_name ) {

			return parent::html_name() . '[]';

		}

		return parent::html_name();

	}

	/**
	 * Return just the <input> HTML.
	 *
	 * Hidden fields don't need wrappers.
	 *
	 * @return string
	 */
	function get_field_html() {

		/**
		 * @var WP_Field_Feature_Base $feature
		 */
		$feature = $this->features[ 'input' ];

		return $feature->get_element_html();

	}
}
