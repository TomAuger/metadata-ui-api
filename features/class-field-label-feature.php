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
   * @return string
   */
  function get_feature_html() {
    $label_html = $this->html_element->get_element_html();
    //WP_Metadata::extract_prefixed_args( $args, )
    return WP_Metadata::get_element_html( $this->outer_tag(), array(), $label_html );
  }

  /**
   * @param WP_Field_Base $field
   * @param array $attributes
   */
  function __construct( $field, $attributes = array() ) {
    parent::__construct( $field, $attributes );
  }

}


