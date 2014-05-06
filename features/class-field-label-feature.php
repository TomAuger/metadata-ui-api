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
   * @param array $feature_args
   */
  function __construct( $field, $feature_args = array() ) {
    parent::__construct( $field, $feature_args );
  }

  /**
   * @return mixed|string
   */
  function html_value() {
    return $this->label_text;
  }

}


