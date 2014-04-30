<?php

/**
 * Class WP_Registry
 */
class WP_Registry extends WP_Metadata_Base {

  /**
   * @var array
   */
  private static $_items = array();

  /**
   * @param string $name
   * @param array $args
   * @return int
   */
  function register_item( $name, $args ) {
    $index = count( self::$_items );
    self::$_items[$name] = $args;
    return $index;
  }

  /**
   * @param string $name
   * @return mixed
   */
  function get_item( $name ) {
    return isset( self::$_items[$name] ) ? self::$_items[$name] : null;
  }

  /**
   * @param string $name
   *
   * @return bool
   */
  function item_exists( $name ) {
    return isset( self::$_items[$name] );
  }

}
