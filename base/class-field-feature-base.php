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
   *
   */
  const WRAPPER_TAG = 'div';

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
   * @var WP_Html_Element
   */
  var $wrapper;


  /**
   * @return array
   */
  static function DELEGATES() {
    return array(
      'html'    => 'html_element',
      'wrapper' => 'wrapper',
    );
  }

  /**
   * @return array
   */
  static function NO_PREFIX() {
    return array(
      'field',
      'wrapper',
    );
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
      $html_attributes = WP_Metadata::extract_prefixed_args( $attributes, 'html' );

      if ( empty( $html_attributes['html_id'] ) ) {
        $html_attributes['html_id'] = $this->html_id();
      }
      if ( empty( $html_attributes['html_name'] ) ) {
        $html_attributes['html_name'] = $this->html_name();
      }
      if ( empty( $html_attributes['html_class'] ) ) {
        $html_attributes['html_class'] = $this->html_class();
      }

      $this->html_element = WP_Metadata::get_html_element( $this->html_tag(), $html_attributes );
    }

    if ( ! is_object( $this->wrapper ) ) {
      $wrapper_attributes = WP_Metadata::extract_prefixed_args( $attributes, 'wrapper' );

      if ( empty( $wrapper_attributes['html_id'] ) ) {
        $wrapper_attributes['html_id'] = $this->wrapper_html_id();
      }
      if ( empty( $wrapper_attributes['html_name'] ) ) {
        $wrapper_attributes['html_name'] = $this->wrapper_html_name();
      }
      if ( empty( $wrapper_attributes['html_class'] ) ) {
        $wrapper_attributes['html_class'] = $this->wrapper_html_class();
      }

      $this->wrapper = WP_Metadata::get_html_element( $this->wrapper_tag(), $wrapper_attributes );
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
   * Return the HTML tag to be wrapper around the field.
   * @return array
   */
  function wrapper_tag() {
    return $this->constant( 'WRAPPER_TAG' );
  }

  /**
   * @return bool|string
   */
  function wrapper_html_id() {
    return $this->html_id() . "-wrapper";
  }

  /**
   * @return bool|string
   */
  function wrapper_html_class() {
    return $this->html_class() . "-wrapper";
  }

  /**
   * @return bool|string
   */
  function wrapper_html_name() {
    return $this->html_name() . "-wrapper";
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
    $this->html_element->element_value = $this->html_value();
    $this->wrapper->element_value = $this->html_element->get_element_html();
    $feature_html = $this->wrapper->get_element_html();
    return $feature_html;
  }

}
