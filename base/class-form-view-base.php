<?php

/**
 * Class WP_Form_View_Base
 */

abstract class WP_Form_View_Base extends WP_Metadata_Base {

  /**
   *
   */
  const HTML_TAG = 'div';

  /**
   * @var WP_Form
   */
  var $form;

  /**
   * @var string
   */
  var $form_method = 'post';

  /**
   * @var string
   */
  var $form_action = '';

  function __construct( $form, $view_name ) {
    $this->form = $form;
  }

  /**
   * @return string
   */
  function get_form_html() {
    $attributes['html_id']    = $this->html_id();
    $attributes['html_name']  = $this->html_name();
    $attributes['html_class'] = $this->html_class();
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
    foreach( $this->form->fields as $field_name => $field ) {
      $fields_html[] = $field->get_field_html();
    }
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
