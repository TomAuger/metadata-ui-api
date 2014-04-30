<?php

/**
 * Class WP_Field_Feature_Base
 */
abstract class WP_Field_Feature_Base extends WP_Metadata_Base {

  /**
   *
   */
  const HTML_TAG = 'div';

  /**
   * @var WP_Field_Base
   */
  var $field;

  /**
   * @var string
   */
  var $feature_type;

  /**
   * @var WP_Html_Element
   */
  var $html_element;

  /**
   * @return array
   */
  static function DELEGATES() {
    return array( 'html_' => 'html_element' );
  }

  /**
   * @return array
   */
  static function NO_PREFIX() {
    return array( 'field' );
  }

  /**
   * @param WP_Field_Base $field
   * @param array $attributes
   */
  function __construct( $field, $attributes = array() ) {

    //$this->field =
    $attributes['field'] = $field;

    parent::__construct( $attributes );

    if ( ! is_object( $this->html_element ) ) {
      $attributes['html_id']    = $this->html_id();
      $attributes['html_name']  = $this->html_name();
      $attributes['html_class'] = $this->html_class();
      $this->html_element = WP_Metadata::get_html_element( $this->html_tag(), $attributes, $this->html_value() );
    }

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
    return str_replace( '_', '-', $this->field->field_name ) . "-field-{$this->feature_type}";
  }

  /**
   * @return bool|string
   */
  function html_class() {
    return "field-{$this->feature_type}";
  }

  /**
   * @return bool|string
   */
  function html_name() {
    return $this->field->field_name;
  }

  /**
   * @return bool|string
   */
  function html_type() {
    return $this->field->constant( 'HTML_TYPE' );
  }

  /**
   * @return mixed
   */
  function html_value() {
    return $this->field->get_value();
  }

  /**
   * @return string
   */
  function get_feature_html() {
    return $this->html_element->get_element_html();
  }

}
