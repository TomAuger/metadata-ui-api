<?php
/**
 * Class WP_Form_View_Base
 */
abstract class WP_Form_View_Base extends WP_Metadata_Base {

	/**
	 *
	 */
	const HTML_TAG = 'div'; // @TODO Should this be WRAPPER_TAG

	/**
	 * @var string
	 */
	var $view_name;

	/**
	 * @var WP_Form
	 */
	var $form;

	/**
	  * Defines the PARAMETERS for the static class factory method 'make_new'.
	  *
	  * @return array
	  */
	 static function PARAMETERS() {

	   return array(
	     '$value',
		   '$parent',
	     '$args',
	   );

	 }

	/**
	 * @param string $view_name
	 * @param string $form
	 * @param array $view_args
	 *
	 * @return WP_Form_View
   *
	 */
	static function make_new( $view_name, $form, $view_args = array() ) {

		$form_view = new WP_Form_View( $view_name, $form, $view_args );

		return $form_view;

	}

	/**
	 * @param string $view_name
	 * @param string $form
	 * @param array $view_args
	 *
	 */
	function __construct( $view_name, $form, $view_args = array() ) {

		$view_args['view_name'] = $view_name;

		$this->form = $form;

		parent::__construct( $view_args );

	}

	/**
	 * @return string
	 */
	function get_form_html() {

		$attributes = array(
			'id'    => $this->html_id(),
			'name'  => $this->html_name(),
			'class' => $this->html_class() . ( ! empty( $attributes[ 'class' ] ) ? " {$attributes['class']}" : '' ),
		);

		$form_html = WP_Metadata::get_element_html( $this->html_tag(), $attributes, $this->get_form_fields_html() );

		return "\n<!-- WP_Metadata Form -->\n\n{$form_html}";

	}

	/**
	 * @return string
	 */
	function get_form_fields_html() {

		$fields_html = array();

		/**
		 * @var WP_Field_Base $field
		 */
		foreach ( $this->form->fields as $field_name => $field ) {
			$fields_html[] = $field->get_field_html();
		}

//		$form_field = new WP_Hidden_Field( "wp_metadata_forms", array(
//			'value' => $this->form->form_name,
//			'storage' => 'memory',
//			'view' => 'hidden',
//			'shared_name' => true,
//			'form' => $this->form,
//		));
//
//		$fields_html[] = $form_field->get_field_html();

		return implode( "\n", $fields_html );

	}


	/**
	 * Return the HTML tag to be used by this class.
	 * @return array
	 */
	function html_tag() {

		return $this->constant( 'HTML_TAG' );

	}

	/**
	 * @return bool|string
	 */
	function html_id() {

		return str_replace( '_', '-', $this->html_name() ) . '-' . $this->html_class();

	}

	/**
	 * @return bool|string
	 */
	function html_class() {

		return "metadata-form";

	}

	/**
	 * @return bool|string
	 */
	function html_name() {

		return $this->form->form_name;

	}

}
