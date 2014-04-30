<?php

/**
 * Class WP_Storage_Base
 */

abstract class WP_Storage_Base extends WP_Metadata_Base {
  /**
   *
   */
  const OBJECT_ID = 'ID';

  /**
   * @var WP_Post|WP_User|object
   */
  var $object;

  /**
   * @var WP_Field_Base
   */
  var $field;

  /**
   * $storage_arg names that should not get a prefix.
   *
   * Intended to be used by subclasses.
   *
   * @return array
   */
  static function NO_PREFIX() {
    return array(
      'field',
      'object',
    );
  }

  /**
   * @param WP_Field_Base $field
   * @param array $storage_args
   */
  function __construct( $field, $storage_args = array() ) {
    $this->field = $field;
    parent::__construct( $storage_args );
  }

  /**
   * @return mixed $value
   */
  function get_value() {
    return null;
  }

  /**
   * @param null|mixed $value
   */
  function update_value( $value = null ) {
  }

  /**
   * Name used for field key.
   *
   * Most common example of a field key would be a meta key.
   *
   * @param string $storage_key
   * @return string
   */
  function storage_key() {
    return "_{$this->field->field_name}";
  }

  /**
   * @return int
   */
  function object_id() {
    return $this->object->{self::OBJECT_ID};
  }

  /**
   * @param $object_id
   */
  function set_object_id( $object_id ) {
    if ( property_exists( $this->object, self::OBJECT_ID ) ) {
      $this->object->{self::OBJECT_ID} = $object_id;
    }
  }

  /**
   *
   * @param $field_name
   * @return bool
   */
  function has_field( $field_name ) {
    return true;
  }

}




