<?php

/**
 * Class WP_Field_Group
 *
 * @method void the_field_group()
 * @method void the_field_group_fields()
 */
class WP_Field_Group extends WP_Metadata_Base {

	/**
	 *
	 */
//	const PREFIX = 'field_group';

	/**
	 * @var string
	 */
	var $field_group_name;

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
	var $field_group_index;

	/**
	 * @var WP_Field_Group_View_Base
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
	 * @param string $field_group_name
	 * @param string|WP_Object_Type $object_type
	 * @param array $field_group_args
	 */
	function __construct( $field_group_name, $object_type, $field_group_args ) {

		$field_group_args['field_group_name']   = $field_group_name;
		$field_group_args['object_type'] = new WP_Object_Type( $object_type );

		parent::__construct( $field_group_args );

	}

	/**
	 * @return array
	 */
	static function CLASS_VALUES() {
		return array(
				'parameters' => array(
						'$value',
						'object_type',
						'$args',
				)
		);
	}

	/**
	 * @return array
	 */
	static function PROPERTIES() {

		return array(
				'view'    => array( 'type' => 'WP_Field_Group_View', 'default' => 'default' ),
				'fields'  => array( 'type' => 'WP_Field_Base[]' ),
		);

	}

	/**
	 * @param string $field_group_name
	 * @param string|WP_Object_Type $object_type
	 * @param array $field_group_args
	 *
	 * @return WP_Field_Group
	 *
	 * @todo Support more than one type of field_group. Maybe. If needed.
	 *
	 */
	static function make_new( $field_group_name, $object_type, $field_group_args = array() ) {

		$field_group = new WP_Field_Group( $field_group_name, $object_type, $field_group_args );

		return $field_group;

	}

	/**
	 *
	 */
	function initialize_class() {

		$this->register_view( 'default', 'WP_Field_Group_View' );

	}

	/**
	 * Register a class to be used as a field_group_view for the current class.
	 *
	 * $wp_field_group->register_view( 'post_admin', 'WP_Post_Adminview' );
	 *
	 * @param string $view_type  The name of the view that is unique for this class.
	 * @param string $class_name The class name for the View object.
	 */
	function register_view( $view_type, $class_name ) {

		WP_Metadata::register_view( 'field_group', $view_type, $class_name, get_class( $this ) );

	}

	/**
	 * @param array $field_group_args
	 */
	function initialize( $field_group_args ) {

		if ( ! is_object( $this->view ) ) {

			$this->set_field_group_view( 'default' );

		}

		$this->initialize_field_group_fields( $field_group_args['object_type'] );

	}

	/**
	 * @param string $view_type
	 */
	function set_field_group_view( $view_type ) {

		if ( ! $this->field_group_view_exists( $view_type ) ) {
			$this->view = false;
		} else {
			$field_group_view_class = $this->get_view_class( $view_type );

			$this->view = new $field_group_view_class( $view_type, $this );
		}

	}

	/**
	 * Does the named field_group view exist
	 *
	 * @param string $view_type The name of the view that is unique for this class.
	 *
	 * @return bool
	 */
	function field_group_view_exists( $view_type ) {

		return WP_Metadata::view_exists( 'field_group', $view_type, get_class( $this ) );

	}

	/**
	 * Retrieve the class name for a named view.
	 *
	 * @param string $view_type The name of the view that is unique for this class.
	 *
	 * @return string
	 */
	function get_view_class( $view_type ) {

		return WP_Metadata::get_view_class( 'field_group', $view_type, get_class( $this ) );

	}

	/**
	 * @param string $object_type
	 * @param bool|array $field_names
	 */
	function initialize_field_group_fields( $object_type, $field_names = false ) {

		$this->fields = array();

		if ( ! $field_names ) {
			$field_names = WP_Metadata::get_field_names( $object_type );
		}

		foreach ( $field_names as $field_name ) {
			$field = WP_Metadata::get_field( $field_name, $object_type, array(
					'field_group' => $this,
			) );

			if ( is_object( $field ) ) {
				$this->add_field( $field );
			}
		}

	}

	/**
	 * @param WP_Field_Base $field
	 */
	function add_field( $field ) {

		$field->field_group                        = $this;
		$this->fields[ $field->field_name ] = $field;

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function get_default_args( $args = array() ) {
		$args                 = parent::get_default_args( $args );
		$args['element_name'] = str_replace( '-', '_', $this->field_group_name );

		return $args;

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
				if ( isset( $values[ $field_name ] ) ) {
					if ( is_null( $values[ $field_name ] ) ) {
						/*
						 * $field->update_value( null ) updates using existing $field->value().
						 */
						$values[ $field_name ] = false;
					}
					$field->update_value( $values[ $field_name ] );
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
		if ( ! ( $result = parent::__call( $method_name, $args ) ) ) {
			/*
			 * Delegate call to view and return it's result to caller.
			 */
			$result = $this->view->{$method_name}( $args );
		}

		return $result;

	}

}

/**
 * Class WP_Field_Group_View
 */
class WP_Field_Group_View extends WP_Field_Group_View_Base {

}

