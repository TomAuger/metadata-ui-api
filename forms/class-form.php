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
	var $view;

	/**
	 *
	 */
	private $_initialized = false;

	/**
	 * @return array
	 */
	static function DELEGATES() {

		return array( 'storage' => 'storage' );

	}

	/**
	 * $form_arg names that should not get a prefix.
	 *
	 * Intended to be used by subclasses.
	 *
	 * @return array
	 */
	static function NO_PREFIX() {

		return array(
			'view',
			'fields',
		);

	}

	function __construct( $form_name, $object_type, $form_args ) {

		$form_args[ 'form_name' ] = $form_name;
		$form_args[ 'object_type' ] = new WP_Object_Type( $object_type );

		parent::__construct( $form_args );

	}

	function initialize_class() {

		$this->register_view( 'default', 'WP_Form_View' );

	}

	/**
	 * @param array $form_args
	 */
	function initialize( $form_args ) {

		if ( !is_object( $this->view ) ) {
			$this->set_form_view( 'default' );
		}

		$this->initialize_form_fields( $form_args[ 'object_type' ] );

	}

	/**
	 * @param string $object_type
	 * @param bool|array $field_names
	 */
	function initialize_form_fields( $object_type, $field_names = false ) {

		$this->fields = array();

		if ( !$field_names ) {
			$field_names = WP_Metadata::get_field_names( $object_type );
		}

		foreach ( $field_names as $field_name ) {
			$field = WP_Metadata::get_field( $field_name, $object_type, array() );

			if ( is_object( $field ) ) {
				$this->add_field( $field );
			}
		}

	}

	/**
	 * @param string $view_name
	 */
	function set_form_view( $view_name ) {

		if ( !$this->form_view_exists( $view_name ) ) {
			$this->view = false;
		}
		else {
			$form_view_class = $this->get_view_class( $view_name );

			$this->view = new $form_view_class( $this, $view_name );
		}

	}

	/**
	 * @param WP_Post|object $object
	 */
	function set_storage_object( $object ) {

		/**
		 * @var WP_Field_Base $field
		 */
		foreach ( $this->fields as $field ) {
			if ( ! is_object( $field->storage->object ) ) {
				$field->storage->object = $object;
			}
		}

	}

	/**
	 * Register a class to be used as a form_view for the current class.
	 *
	 * $wp_form->register_view( 'post_admin', 'WP_Post_Adminview' );
	 *
	 * @param string $view_name The name of the view that is unique for this class.
	 * @param string $class_name The class name for the View object.
	 */
	function register_view( $view_name, $class_name ) {

		WP_Metadata::register_view( 'form', $view_name, $class_name, get_class( $this ) );

	}

	/**
	 * Does the named form view exist
	 *
	 * @param string $view_name The name of the view that is unique for this class.
	 *
	 * @return bool
	 */
	function form_view_exists( $view_name ) {

		return WP_Metadata::view_exists( 'form', $view_name, get_class( $this ) );

	}

	/**
	 * Retrieve the class name for a named view.
	 *
	 * @param string $view_name The name of the view that is unique for this class.
	 *
	 * @return string
	 */
	function get_view_class( $view_name ) {

		return WP_Metadata::get_view_class( 'form', $view_name, get_class( $this ) );

	}

	/**
	 * @param WP_Field_Base $field
	 */
	function add_field( $field ) {

		$this->fields[ $field->field_name ] = $field;

	}

	/**
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return mixed
	 */
	function __call( $method_name, $args = array() ) {

		/*
		 * If method was the_*() method, parent __call() will fall through and return false.
		 */
		if ( !( $result = parent::__call( $method_name, $args ) ) ) {
			/*
			 * Delegate call to view and return it's result to caller.
			 */
			$result = $this->view->$method_name( $args );
		}

		return $result;

	}

}
