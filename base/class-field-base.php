<?php

/**
 * Class WP_Field_Base
 *
 * @mixin WP_Field_View_Base
 */
class WP_Field_Base extends WP_Metadata_Base {

	/**
	 *
	 */
	const FIELD_TYPE = 'text';

	/**
	 *
	 */
//	const PREFIX = 'field';

	/**
	 * @var bool|string
	 */
	var $field_name = false;

	/**
	 * @var bool|string
	 */
	var $field_type = false;

	/**
	 * @var bool
	 */
	var $field_required = false;

	/**
	 * @var mixed
	 */
	var $field_default = null;

	/**
	 * @var array
	 */
	var $field_args;

	/**
	 * @var bool|string
	 */
	var $storage = false;

	/**
	 * @var bool|WP_Object_Type
	 */
	var $object_type = false;

	/**
	 * @var string|WP_Field_View_Base
	 */
	var $view = false;

	/**
	 * @var WP_Form
	 */
	var $form;

	/**
	 * @var bool|int
	 */
	protected $_field_index = false;

	/**
	 * @var null|mixed
	 */
	protected $_value = null;

	/**
	 * Holds the object being edited.
	 *
	 * @var WP_Post|WP_User|object
	 */
	protected $_object;

	/**
	 * @param string $field_name
	 * @param array $field_args
	 */
	function __construct( $field_name, $field_args = array() ) {

		$this->field_name = $field_name;

		if ( isset( $field_args['form'] ) ) {
			/**
			 * This may be needed by subobjects before it is assigned
			 * in $this->assign_args(), so do now rather than wait.
			 */
			$this->form = $field_args['form'];
			unset( $field_args['form'] );
		}

		parent::__construct( $field_args );

	}

	/**
	 */
	static function CLASS_VALUES() {

		/*
		 * These are the feature keys for the base field view object.
		 * If you custom view needs different ones you'll need to handle
		 * in your view or maybe in your field.
		 */
		$feature_keys = 'label|input|help|message|infobox';

		$shortnames = array(
			'^view_type$'                                       => 'view:view_type',
			'^label$'                                           => 'view:features[label]:label_text',
			'^element:(.+)$'                                    => 'view:features[input]:element:$1',
			"^({$feature_keys}):?wrapper:(.+)$"                 => 'view:features[$1]:wrapper:$2',
			"^({$feature_keys}):(element:)?(.+)$"               => 'view:features[$1]:element:$3',
			"^features\[({$feature_keys})\]:(element:)?(.+)$"   => 'view:features[$1]:element:$3',
		);

		return array(
				'defaults'   => array(
						'view:view_type' => 'text'
				),
				'shortnames' => $shortnames,
				'parameters' => array(
						'$value',
						'object_type',
						'$args',
				)
		);
	}

	/**
	 * Returns an array of object properties and their annotations.
	 *
	 * @return array
	 */
	static function PROPERTIES() {

		return array(
				'value'   => array( 'type' => 'mixed' ),
				'form'    => array( 'type' => 'WP_Form', 'auto_create' => false ),
				'storage' => array( 'type' => 'text', 'default' => 'meta' ),
				'view'    => array( 'type' => 'WP_Field_View_Base' ),
		);

	}

	/**
	 * Make a New Field object
	 *
	 * @param string $field_name
	 * @param string|WP_Object_Type $object_type
	 * @param array $field_args
	 *
	 * @return WP_Field_Base
	 *
	 */
	static function make_new( $field_name, $object_type, $field_args = array() ) {

		$field = false;

		if ( ! isset( $field_args['field_type'] ) ) {
			/*
			 * We have to do this normalization of the 'type' $arg prior to
			 * the Field classes __construct() because it drives the class used
			 * to instantiate the Field. All other $args can be normalized
			 * in the Field class constructor.
			 */
			if ( ! isset( $field_args['type'] ) ) {

				$field_args['field_type'] = 'text';

			} else {

				$field_args['field_type'] = $field_args['type'];

				unset( $field_args['type'] );

			}
		}

		/**
		 * @var string|object $field_type_args If string, a class. If an array, call recursively.
		 */
		$field_type_args = WP_Metadata::get_field_type_args( $field_args['field_type'] );

		if ( is_string( $field_type_args ) && class_exists( $field_type_args ) ) {

			/**
			 * Field type is a Class name
			 */
			$field = new $field_type_args( $field_name, $field_args );

		} else if ( is_array( $field_type_args ) ) {

			/**
			 * Field Type passed to make_new() is a 'Prototype'
			 */
			$field_args = wp_parse_args( $field_args, $field_type_args );

			$field = self::make_new( $field_name, $object_type, $field_args );

		} else {

			$field = null;

		}

		return $field;

	}

	/**
	 * @return mixed
	 */
	function form_element_name() {

		return $this->form->form_name;

	}

	/**
	 * @return bool|string
	 */
	function initial_element_name() {

		return $this->field_name;

	}

	/**
	 * @return string
	 */
	function initial_element_id() {

		return str_replace( '_', '-', $this->element->get_name() ) . '-field';

	}

	/**
	 * @param string $view_type
	 * @param array $view_args
	 */
	function initialize_field_view( $view_type, $view_args = array() ) {

		if ( ! WP_Metadata::field_view_exists( $view_type ) ) {
			$this->view = false;
		} else {
			$view_args['view_type'] = $view_type;
			$view_args['field']     = $this; // This is redundant, but that's okay
			$this->view             = $this->make_field_view( $view_type, $view_args );
		}

	}

	/**
	 * @param string $view_type
	 * @param array $view_args
	 *
	 * @return WP_Field_View_Base
	 */
	function make_field_view( $view_type, $view_args = array() ) {

		return WP_Field_View_Base::make_new( $view_type, $this, $view_args );

	}

	/**
	 * @param string $feature_type
	 * @param array $feature_args
	 *
	 * @return null|WP_Field_Feature_Base
	 */
	function make_field_feature( $feature_type, $feature_args ) {

		return WP_Metadata::make_field_feature( $this, $feature_type, $feature_args );

	}

	/**
	 * @return WP_Field_Feature_Base
	 */
	function input_feature() {

		return $this->view->input_feature();

	}

	/**
	 * Determine is the field is configured correctly for storage.
	 */
	function has_storage() {

		return preg_match( '#(option|memory)#', $this->storage ) || (
			! empty( $this->storage ) &&
			is_object( $this->_object ) &&
			preg_match( '#(post|meta|term)#', $this->storage )
		);

	}

	/**
	 *
	 */
	function value() {

		if ( is_null( $this->_value ) && $this->field->has_storage() ) {

			$this->_value = $this->get_value();

		}

		return $this->_value;

	}

	/**
	 *
	 */
	function get_value() {

		switch ( $this->storage ) {
			case 'post':
				$value = $this->_object->{$this->field->field_name};
				break;
			case 'meta':
				$value = get_metadata( 'post', $this->object_id(), $this->storage_key(), true );
				break;
			case 'term':
				break;
			case 'option':
				$value = get_option( $this->storage_key(), true );
				break;
			case 'memory':
				$value = $this->_object;
				break;
			default:
				$value = null;
		}
		return $value;

	}

	/**
	 * @param null|mixed $value
	 */
	function update_value( $value = null ) {

		if ( ! is_null( $value ) ) {
			$this->set_value( $value );
		}

		switch ( $this->storage ) {
			case 'core':
				// @var wpdb $wpdb
				global $wpdb;
				$wpdb->update( $wpdb->posts,
					array( $this->field_name => esc_sql( $this->value() ) ),
					array( 'ID' => $this->object_id() )
				);
				break;
			case 'meta':
				update_metadata( 'post', $this->object_id(), $this->storage_key(), esc_sql( $this->value() ) );
				break;
			case 'taxonomy':
				// @todo
				break;
			case 'option':
				update_option( $this->storage_key(), esc_sql( $this->value() ) );
				break;
			case 'memory':
				$this->_object = $this->value();
				break;
		}

	}

	/**
	 * Get Storage key for the Storage
	 *
	 * @return string
	 */
	function storage_key() {

		switch ( $this->storage ) {
			case 'core':
				$storage_key = $this->field_name;
				break;
			case 'meta':
				$storage_key = "_{$this->field_name}";
				break;
			case 'taxonomy':
				$storage_key = false;  // @todo
				break;
			case 'option':
				if ( $group = $this->object_type->subtype ) {
					$storage_key = "_{WP_Metadata::$prefix}{$group}[{$this->field_name}]";
				} else {
					$storage_key = "_{WP_Metadata::$prefix}{$this->field_name}";
				}
				break;
			default:
				$storage_key = null;
		}
		return $storage_key;

	}

	/**
	 * @return bool
	 */
	function has_field() {

		switch ( $this->storage ) {
			case 'core':
				$has_field = property_exists( $this->_object, $this->field_name );
				break;
			case 'meta':
				$has_field = get_metadata( 'post', $this->object_id(), $this->storage_key(), true );
				break;
			case 'taxonomy':
				$has_field = false;  // @todo
				break;
			case 'option':
				$has_field = get_option( $this->storage_key(), true );
				break;
			case 'memory':
				$has_field = true;
				break;
			default:
				$has_field = null;
		}
		return $has_field;

	}

	/**
	 * @param mixed $value
	 */
	function set_value( $value ) {

		$this->_value = $value;

	}


	/**
	 * @param $object_id
	 */
	function set_object_id( $object_id ) {

		if ( is_object( $this->_object ) && property_exists( $this->_object, 'ID' ) ) {
			$this->_object->ID = $object_id;
		}

	}

	/**
	 * @return int
	 */
	function object_id() {

		if ( is_object( $this->_object ) && property_exists( $this->_object, 'ID' ) ) {
			$object_id = $this->_object->ID;
		}

		return $object_id;

	}

	/**
	 * @param object $object
	 */
	function set_object( $object ) {

		$this->_object = $object;

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function get_shortnames( $args = array() ) {

		$shortnames = parent::get_shortnames();

		$view_class = WP_Metadata::get_field_view_type_args( $args['view:view_type'] );

		if ( class_exists( $view_class ) && $attributes = $this->get_view_input_attributes( $view_class ) ) {

			unset( $attributes['form'] ); // Reserve 'form' for instances of WP_Form.

			$attributes = implode( '|', $attributes );

			$shortnames["^({$attributes})$"] = 'view:input:element:$1';

		}

		return $shortnames;

	}

	/**
	 *
	 * @param string|bool $view_class
	 *
	 * @return string[]
	 */
	function get_view_input_attributes( $view_class = false ) {

		if ( ! $view_class ) {

			$view_class = get_class( $this->view );

		}

		$input_tag = WP_Metadata::get_view_input_tag( $view_class );

		return $input_tag ? WP_Metadata::get_view_element_attributes( $input_tag ) : array();
	}

	/**
	 * Delegate accesses for missing poperties to the $_field_view property
	 *
	 * @param string $property_name
	 *
	 * @return mixed
	 */
	function __get( $property_name ) {

		return property_exists( $this->view, $property_name ) ? $this->view->$property_name : null;

	}

	/**
	 * Delegate accesses for missing poperties to the $_field_view property
	 *
	 * @param string $property_name
	 * @param mixed $value
	 *
	 */
	function __set( $property_name, $value ) {

		if ( property_exists( $this->view, $property_name ) ) {

			$this->view->$property_name = $value;

		}

	}

	/**
	 * Delegate calls for missing methods to the $_field_view property
	 *
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return mixed
	 */
	function __call( $method_name, $args = array() ) {

		return method_exists( $this->view, $method_name ) ? call_user_func_array( array(
				$this->view,
				$method_name
		), $args ) : null;

	}

}
