<?php

/**
 * Class WP_Form
 *
 * @method void the_form()
 * @method void the_form_fields()
 */

class WP_Form extends WP_Metadata_Base {

  /**
   *
   */
  const PREFIX = 'form_';

  /**
   * @var string
   */
  var $form_name;

  /**
   * @var string|WP_Object_Type
   */
  var $object_type;

  /**
   * @var array
   */
  var $fields = array();

  /**
   * @var int
   */
  var $form_index;

  /**
   * @var array
   */
  protected $_form_view;

  /**
   * $form_arg names that should not get a prefix.
   *
   * Intended to be used by subclasses.
   *
   * @return array
   */
  static function NO_PREFIX() {
    return array( 'fields' );
  }

  function __construct( $form_name, $object_type, $form_args ) {
    $this->register_form_view( 'default', 'WP_Form_View' );
    $this->form_name = $form_name;
    $this->object_type = new WP_Object_Type( $object_type );
    parent::__construct( $form_args );
    if ( ! is_object( $this->_form_view ) ) {
      $this->set_form_view( 'default' );
    }
  }

  /**
   * @param string $view_name
   */
  function set_form_view( $view_name ) {
    if ( ! $this->form_view_exists( $view_name ) ) {
      $this->_form_view = false;
    } else {
      $form_view_class = $this->get_form_view( $view_name );
      $this->_form_view = new $form_view_class( $this, $view_name );
    }
  }

  /**
   * Register a class to be used as a form_view for the current class.
   *
   * $wp_form->register_form_view( 'post_admin', 'WP_Post_Admin_Form_View' );
   *
   * @param string $view_name The name of the view that is unique for this class.
   * @param string $class_name The class name for the View object.
   */
  function register_form_view( $view_name, $class_name ) {
    WP_Metadata::register_view( 'form', $view_name, $class_name, get_class( $this ) );
  }

  /**
   * Does the named form view exist
   *
   * @param string $view_name The name of the view that is unique for this class.
   * @return bool
   */
  function form_view_exists( $view_name ) {
    return WP_Metadata::view_exists( 'form', $view_name, get_class( $this ) );
  }

  /**
   * Retrieve the class name for a named view.
   *
   * @param string $view_name The name of the view that is unique for this class.
   * @return string
   */
  function get_form_view( $view_name ) {
    return WP_Metadata::get_view( 'form', $view_name, get_class( $this ) );
  }

  /**
   * @param string $field_name
   * @param WP_Field_Base $field
   */
  function add_field( $field_name, $field ) {
    $this->fields[$field->field_name] = $field;
  }

  /**
   * @param string $method_name
   * @param array $args
   * @return mixed
   */
  function __call( $method_name, $args = array() ) {
    /*
     * If method was the_*() method, parent __call() will fall through and return false.
     */
    if ( ! ( $result = parent::__call( $method_name, $args ) ) ) {
      /*
       * Delegate call to view and return it's result to caller.
       */
      $result = $this->_form_view->$method_name( $args );
    }
    return $result;
  }

}
