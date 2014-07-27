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
	const PREFIX = 'form';

	/**
	 * @var string
	 */
	var $form_name;

	/**
	 * @var string|WP_Object_Type
	 */
	var $object_type;

	/**
	 * @var WP_Field_Base[]
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
 	 * @var WP_Storage_Base
 	 */
	var $storage;

	/**
	 *
	 */
	private $_initialized = false;

  /**
 	 * @return array
 	 */
  static function PROPERTIES() {

    return array(
      'storage' => array( 'prefix' => 'storage', 'type' => 'WP_Storage_Base' ),
      'view'    => array( 'prefix' => 'view', 'type' => 'WP_Form_View' ),
      'fields'  => array( 'type' => 'WP_Field_Base[]' ),
    );

  }

  /**
   * Defines the PARAMETERS for the static class factory method 'make_new'.
   *
   * @return array
   */
  static function PARAMETERS() {

    return array(
      '$value',
      'object_type',
      '$args',
    );

  }

	/**
	 * @param string $form_name
	 * @param string|WP_Object_Type $object_type
	 * @param array $form_args
	 */
	function __construct( $form_name, $object_type, $form_args ) {

		$form_args[ 'form_name' ] = $form_name;
		$form_args[ 'object_type' ] = new WP_Object_Type( $object_type );

		parent::__construct( $form_args );

	}

	/**
	 *
	 */
	function initialize_class() {

    $this->register_view( 'default', 'WP_Form_View' );

	}

	/**
	 * @param array $form_args
	 */
	function initialize( $form_args ) {

		if ( ! is_object( $this->view ) ) {

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
			$field = WP_Metadata::get_field( $field_name, $object_type, array(
				'form' => $this,
			));

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
	 * @return mixed
	 */
	function html_name() {

		return str_replace( '-', '_', $this->form_name );

	}

	/**
	 * @param WP_Field_Base $field
	 */
	function add_field( $field ) {

		$field->form = $this;
		$this->fields[ $field->field_name ] = $field;

	}

	/**
	 *
	 */
	function set_object( $object ) {
		/**
		 * @var WP_Field_Base $field
		 */
		foreach ( $this->fields as $field ) {
			$field->set_object( $object );
		}
	}

	/**
	 *
	 */
	function update_values( $values = false ) {
		if ( false === $values ) {
			/**
			 * @var WP_Field_Base $field
			 */
			foreach ( $this->fields as $field ) {
				$field->update_value();
			}
		} else if ( is_array( $values ) ) {
			/**
			 * @var WP_Field_Base $field
			 */
			foreach ( $this->fields as $field_name => $field ) {
				if ( isset( $values[$field_name] ) ) {
					if ( is_null( $values[$field_name] ) ) {
						/*
						 * $field->update_value( null ) updates using existing $field->value().
						 */
						$values[$field_name] = false;
					}
					$field->update_value( $values[$field_name] );
				}
			}
		}
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

	/**
	 * @param array $form_args
	 *
	 * @return array
	 */
	function reject_args( $form_args ) {

		unset( $form_args[ 'view' ] );

		return $form_args;

	}

	/**
	 * @param array $form_args
	 *
	 * @return array
	 */
	function pre_delegate_args( $form_args ) {

		if ( isset( $form_args[ 'view' ] ) ) {

			if ( false !== $form_args[ 'view' ] ) {

				$this->view = $form_args['view'];

			}

		} else {

		 	$this->view = 'default';
		}

		return $form_args;

	}



  /**
 	 * @param string $form_name
 	 * @param string|WP_Object_Type $object_type
 	 * @param array $form_args
 	 *
 	 * @return WP_Form
   *
   * @todo Support more than one type of form. Maybe. If needed.
   *
 	 */
 	static function make_new( $form_name, $object_type, $form_args = array() ) {

 		$form = new WP_Form( $form_name, $object_type, $form_args );

 		return $form;

 	}

}
