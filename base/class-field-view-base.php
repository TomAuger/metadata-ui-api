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
  const WRAPPER_TAG = 'div';

  /**
   * @var string
   */
  var $view_name;

  /**
   * @var WP_Field_Base
   */
  var $field;

  /**
   * @var bool|array
   */
  var $features = false;

  /**
   * @var WP_Html_Element
   */
  var $wrapper;

  /**
   * CONSTANT method that maps $arg prefixes to delegated properties
   *
   * @return array
   */
  static function DELEGATES() {
    return array(
      'label'   => 'label',
      'input'   => 'input',
      'help'    => 'help',
      'message' => 'message',
      'infobox' => 'infobox'
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
   * @param string $view_name
   * @param array $view_args
   */
  function __construct( $view_name, $view_args = array() ) {
    $view_args['view_name'] = $view_name;
    if ( ! empty( $view_args['field'] ) ) {
      /*
       * Set $this->field before parent::__construct() because other initializes depend on it.
       */
      $this->field = $view_args['field'];
    }
    $this->features = array_fill_keys( $this->get_feature_types(), array() );
    parent::__construct( $view_args );

    if ( ! is_object( $this->wrapper ) ) {
      $wrapper_attributes = WP_Metadata::extract_prefixed_args( $view_args, 'wrapper' );

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
   * @param $args
   */
  function initialize( $args ) {
    foreach( $this->get_feature_types() as $feature_type ) {
      $this->delegated_args[$feature_type]['feature_type'] = $feature_type;
      $feature = $this->field->make_field_feature( $feature_type, $this->get_feature_args( $feature_type ) );
      $this->features[$feature_type] = $feature;
    }
  }

  /**
   * @param string $feature_type
   *
   * @return array
   */
  function get_feature_args( $feature_type ) {
    $feature_args = array_merge( $this->field->delegated_args[$feature_type], $this->delegated_args[$feature_type] );
    return $feature_args;
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
    return str_replace( '_', '-', $this->field->field_name ) . '-' . $this->wrapper_html_class();
  }

  /**
   * @return bool|string
   */
  function wrapper_html_class() {
    return "metadata-field-wrapper";
  }

  /**
   * @return bool|string
   */
  function wrapper_html_name() {
    return "{$this->field->field_name}-wrapper";
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
    return $this->features ? array_keys( $this->features ) : $this->_call_lineage_collect_array_elements( 'FEATURE_TYPES' );
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
          ? $this->field[$property_name]
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
   * @param string $property_name
   *
   * @return bool
   */
  function __isset( $property_name ) {
    return isset( $this->features[$property_name] );
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
      /**
       * @var WP_Field_Feature_Base $feature
       */
      $feature = $this->features[$feature_type];
      $features_html[$feature_type] = $feature->get_feature_html();
    }
    return implode( "\n", $features_html );
  }

  /**
   * @return string
   */
  function get_field_html() {
    $this->wrapper->element_value = $this->get_features_html();
    $feature_html = $this->wrapper->get_element_html();
    return $feature_html;
  }

}
