<?php
/**
 * Class WP_Field_Base
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
	const HTML_TYPE = 'text';

	/**
	 *
	 */
	const PREFIX = 'field';

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
	 * @var bool|WP_Storage_Base
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
	 * @return array
	 */
	static function TRANSFORMS() {

		static $transforms;
		if ( ! isset( $transforms ) ) {
			/**
			 * Create a regex to insure delegated and no_prefix $args are not matched
			 * nor are $args that contain underscores.
			 *
			 * @see     http://stackoverflow.com/a/5334825/102699 for 'not' regex logic
			 * @see     http://www.rexegg.com/regex-lookarounds.html for negative lookaheads
			 * @see     http://ocpsoft.org/tutorials/regular-expressions/and-in-regex/ for logical 'and'
			 * @see     http://www.regular-expressions.info/refadv.html for "Keep text out of the regex match"
			 *
			 * @example Regex if 'foo' and 'bar' are no_prefix or contained:
			 *
			 *  '^(?!(?|foo|bar)$)(?!.*_)(.*)'
			 *
			 * @note    Other similar regex that might work the same
			 *
			 *  '^(?!foo$)(?!bar$)(?!.*_)(.*)';
			 *  '^(?!(?>(foo|bar))$)(?!.*_)(.*)'
			 *  '^(?!\K(foo|bar)$)(?!.*_)(.*)'
			 *
			 * Example matches any string except 'foo', 'bar' or one containing an underscore ('_').
			 */

			$attributes = array_merge( WP_Metadata::get_html_attributes( 'input' ) );

			unset( $attributes['form'] ); // Reserve 'form' for instances of WP_Form.

			$attributes = implode( '|', array_keys( $attributes ) );

			$transforms = array(
				'^label$'                           => 'view:label:label_text',
				'^label:([^_]+)$'                   => 'view:label:$1',
				"^({$attributes})$"                 => 'view:input:html:$1',
				'^(input|html):([^_]+)$'            => 'view:input:html:$2',
				'^(input:)?wrapper:([^_]+)$'        => 'view:input:wrapper:html:$2',
			);
		}

		return $transforms;

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
      'view'    => array( 'type' => 'WP_Field_View', 'parameters' => array( '$object_type' => 'object_type' ) ),
      'storage' => array( 'type' => 'WP_Storage_Base', 'default' => 'meta' ),
    );

	}

	/**
  * Defines the make_new() PARAMETERS in order they need to be passed.
  *
  * @return array
  */
 static function PARAMETERS() {

   return array(
     '$value',
     '$object_type',
     '$args',
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

		if ( ! isset( $field_args[ 'field_type' ] ) ) {
			/*
			 * We have to do this normalization of the 'type' $arg prior to
			 * the Field classes __construct() because it drives the class used
			 * to instantiate the Field. All other $args can be normalized
			 * in the Field class constructor.
			 */
			if ( ! isset( $field_args[ 'type' ] ) ) {

				$field_args[ 'field_type' ] = 'text';

			} else {

				$field_args[ 'field_type' ] = $field_args[ 'type' ];

				unset( $field_args[ 'type' ] );

			}
		}

		/**
		 * @var string|object $field_type If string, a class. If object a filepath to load a class and $args
		 */
		$field_type = WP_Metadata::get_field_type( $field_args[ 'field_type' ] );

		if ( is_object( $field_type ) ) {
			/**
			 * Field type is Class name with external filepath
			 */

			if ( $field_type->filepath ) {

				require_once( $field_type->filepath );

			}

			$field_type = $field_type->field_args;
		}

		if ( is_string( $field_type ) && class_exists( $field_type ) ) {

			/**
			 * Field type is a Class name
			 */
			$field = new $field_type( $field_name, $field_args );

		} else if ( is_array( $field_type ) ) {

			/**
			 * Field type is a 'Prototype'
			 */
			$field_args = wp_parse_args( $field_args, $field_type );

			$field = self::make_new( $field_name, $object_type, $field_args );

		}

		return $field;

	}

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
	 * @return mixed
	 */
	function form_html_name() {

		return $this->form->html_name();

	}

//
//	/**
//	 * @param array $field_args
//	 *
//	 * @return array
//	 */
//	function reject_args( $field_args ) {
//
//		unset( $field_args[ 'view' ] );
//
//		unset( $field_args[ 'form' ] );
//
//		return $field_args;
//
//	}

//	/**
//	 * @param array $field_args
//	 *
//	 * @return array
//	 */
//	function pre_assign_args( $field_args ) {
//
//		if ( ! empty( $field_args[ 'form' ] ) ) {
//
//			$this->form = $field_args[ 'form' ];
//
//		}
//
//		if ( $this->form && empty( $this->form->view ) ) {
//
//			$this->view = null;
//
//		} else if ( isset( $field_args[ 'view' ] ) ) {
//
//			if ( false !== $field_args[ 'view' ] ) {
//
//				$this->view = $field_args['view'];
//
//			}
//
//		} else {
//
//		 	$this->view = 'default';
//
//		}
//
//		return $field_args;
//
//	}

	/**
	 * Register the default view for this class.
	 */
	function initialize_class() {

		WP_Metadata::register_field_view( 'default', 'WP_Field_View' );

	}

//	function initialize( $field_args ) {
//
////		$this->initialize_field_view( $this->view, $this->get_view_args() );
////
////		$this->initialize_storage( $this->storage, $this->get_storage_args() );
//
//	}

	/**
	 * @return array
	 */
	function get_storage_args() {
		trigger_error( 'Need to fix ' . __CLASS__ . '::' . __METHOD__ );
		return $this->delegated_args[ 'storage' ];

	}

	/**
	 * @return array
	 */
	function get_view_args() {

		return array_merge(
			$this->view,
			WP_Metadata::collect_args( $this->args, $this->get_view_contained() )
		);

	}

	/**
	 * @return array
	 */
	function get_view_contained() {

		return array();

	}

	/**
	 * @param string $view_name
	 * @param array $view_args
	 */
	function initialize_field_view( $view_name, $view_args = array() ) {

		if ( ! WP_Metadata::field_view_exists( $view_name ) ) {
			$this->view = false;
		} else {
			$view_args[ 'view_name' ] = $view_name;
			$view_args[ 'field' ] = $this; // This is redundant, but that's okay
			$this->view = $this->make_field_view( $view_name, $view_args );
		}

	}

	/**
	 * @param string $storage_type_name
	 * @param array $storage_type_args
	 */
	function initialize_storage( $storage_type_name, $storage_type_args = array() ) {

		if ( ! WP_Metadata::storage_type_exists( $storage_type_name ) ) {
			$storage_type_name = WP_Meta_Storage::STORAGE_TYPE;
		}
		$storage_type_args[ 'owner' ] = $this;
		$this->storage = $this->make_storage( $storage_type_name, $storage_type_args );


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
	 * @param string $storage_type
	 * @param array $storage_args
	 *
	 * @return null|WP_Storage_Base
	 */
	function make_storage( $storage_type, $storage_args ) {

		return WP_Metadata::make_storage( $this, $storage_type, $storage_args );

	}

	/**
	 * @param string $view_name
	 * @param array $view_args
	 *
	 * @return WP_Field_View
	 */
	function make_field_view( $view_name, $view_args = array() ) {

    return WP_Metadata::make_field_view( $this, $view_name, $view_args );

	}


	/**
	 * Retrieve the class name for a named view.
	 *
	 * @param bool|string $view_name The name of the view that is unique for this class.
	 *
	 * @return string
	 */
	function get_view_class( $view_name = false ) {

		if ( !$view_name ) {
			$view_name = $this->view;
		}

		return is_object( $this->view )
		  ? get_class( $this->view )
		  : WP_Metadata::get_view_class( 'field', $view_name, get_class( $this ) );

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
	 * @return bool|string
	 */
	function storage_key() {

		return $this->field_name;

	}

	/**
	 *
	 */
	function get_value() {

		return $this->storage->get_value( $this->storage_key() );

	}

	/**
	 * @param mixed $value
	 */
	function set_value( $value ) {

		$this->_value = $value;

	}

//	/**
//	 * @return WP_Field_Input_Feature
//	 */
//	function get_input_feature() {
//
//		return $this->view->features['input'];
//
//	}

	/**
	 * @param null|mixed $value
	 */
	function update_value( $value = null ) {

		if ( ! is_null( $value ) ) {
			$this->set_value( $value );
		}
		if ( $this->has_storage() ) {
			$this->storage->update_value( $this->value() );
		}

	}

	/**
	 * Determine is the storage property contains a "Storage" object.
	 */
	function has_storage() {

		/**
		 * Use "Structural Typing" to determine is $this->storage is a storage
		 *
		 * Structural Typing provides for maximum flexibility while still being able to
		 * recognize (most) valid and invalid objects. The only real downside is if
		 * an object is inspected and *coincidentally* has the same structure but
		 * is not an object of the appropriate type. In this case that danger is low.
		 *
		 * @see http://en.wikipedia.org/wiki/Structural_type_system
		 * @see http://stackoverflow.com/questions/12720585/what-is-structural-typing-for-interfaces-in-typescript
		 */
		return method_exists( $this->storage, 'get_value' ) && method_exists( $this->storage, 'update_value' );

	}

	/**
	 * @param object $object
	 */
	function set_object( $object ) {

		$this->storage->object = $object;

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
