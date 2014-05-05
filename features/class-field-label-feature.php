<?php

/**
 * Class WP_Field_Label_Feature
 */
class WP_Field_Label_Feature extends WP_Field_Feature_Base {

  /**
   *
   */
  const HTML_TAG = 'label';

  /**
   * @var string
   */
  var $label_text;

  /**
   * @param WP_Field_Base $field
   * @param array $attributes
   */
  function __construct( $field, $attributes = array() ) {
    parent::__construct( $field, $attributes );
  }

  /**
   * @return mixed|string
   */
  function html_value() {
    return $this->label_text;
  }

}


