<?php

/**
 * Class WP_Field_View_Base
 * @mixin WP_Field_Base
 * @property WP_Field_Feature_Base $input
 * @property WP_Field_Feature_Base $label
 * @property WP_Field_Feature_Base $help
 * @property WP_Field_Feature_Base $message
 * @property WP_Field_Feature_Base $infobox
 */
abstract class WP_Field_View_Base extends WP_Metadata_Base {

  /**
   *
   */
  const HTML_TAG = 'div';

  /**
   * @var string
   */
  var $view_name;

  /**
   * @var WP_Field_Base
   */
  var $field;

  /**
   * @var array
   */
  var $features = array();

  /**
   * CONSTANT method that maps $arg prefixes to delegated properties
   *
   * @return array
   */
  static function DELEGATES() {
    return array(
      'label_'   => 'label',
      'input_'   => 'input',
      'help_'    => 'help',
      'message_' => 'message',
      'infobox_' => 'infobox'
    );
  }
  /**
   * CONSTANT method that returns array of feature types for this class.
   *
   * @return array
   */
  function FEATURE_TYPES() {
    return array_values( $this->DELEGATES() );
  }

  /**
   * @param array $view_name
   * @param array $args
   */
  function __construct( $view_name, $args = array() ) {
    $args['view_name'] = $view_name;
    if ( isset( $args['field'] ) ) {
      $this->field = $args['field'];
    }
    $this->features = array_fill_keys( $this->get_feature_types(), array() );
    parent::__construct( $args );
  }

  /**
   * @param $args
   */
  function initialize_args( $args ) {
    foreach( $this->get_feature_types() as $feature_type ) {
      $this->delegated_args[$feature_type]['feature_type'] = $feature_type;
      $feature = $this->field->make_field_feature( $feature_type, $this->delegated_args[$feature_type] );
      $this->features[$feature_type] = $feature;
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
   * Delegate to $field explicitly since it is defined in base class.
   * @return array
   */
  function get_prefix() {
    return $this->field->get_prefix();
  }

  /**
   * Delegate to $field explicitly since it is defined in base class.
   * @return array
   */
  function get_no_prefix() {
    return $this->field->get_no_prefix();
  }

  /**
   * Gets array of field feature type names
   *
   * @return array
   */
  function get_feature_types() {
    return 0 == count( $this->features )
      ? $this->_call_lineage_collect_array_elements( 'FEATURE_TYPES' )
      : array_keys( $this->features );
  }

  /**
   * Delegate accesses for missing poperties to the $field property
   *
   * @param string $property_name
   * @return mixed
   */
  function __get( $property_name ) {
    return isset( $this->features[$property_name] )
      ? $this->features[$property_name]
      : ( property_exists( $this->field, $property_name )
          ? $this->field->$property_name
          : null
        );
  }

  /**
   * Delegate accesses for missing poperties to the $field property
   *
   * @param string $property_name
   * @param mixed $value
   * @return mixed
   */
  function __set( $property_name, $value ) {
    return isset( $this->features[$property_name] )
      ? $this->features[$property_name] = $value
      : ( property_exists( $this->field, $property_name )
          ? $this->field->$property_name = $value
          : null
        );
  }

  /**
   * Delegate calls for missing methods to the $field property
   *
   * @param string $method_name
   * @param array $args
   * @return mixed
   */
  function __call( $method_name, $args = array() ) {
    return method_exists( $this->field, $method_name )
      ? call_user_func_array( array( $this->field, $method_name ), $args )
      : null;
  }

  /**
   * @param string $member_name
   *
   * @return bool
   */
  function __isset( $member_name ) {
    return isset( $this->features[$member_name] );
  }

  /**
   * @param array $attributes
   * @return array
   */
  function filter_html_attributes( $attributes ) {
    return $attributes;
  }

  /**
   * @return array
   */
  function get_features_html() {
    $features_html = array();
    foreach( $this->get_feature_types() as $feature_type ) {
      $features_html[$feature_type] = $this->$feature_type->get_feature_html();
    }
    return $features_html;
  }

  /**
   * @return string
   */
  function get_field_html() {
    $features_html = $this->get_features_html();
    return WP_Metadata::get_element_html(
      $this->html_tag(),
      $this->filter_html_attributes( array(
        'html_id'    => "{$this->field->field_name}-field-row",
        'html_class' => 'field-row',  // @todo Create html_id()/html_class() methods for this.
      )),
      implode( "\n", $features_html )
    );
  }

}
