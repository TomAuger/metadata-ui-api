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
	const PREFIX = 'field_';

	/**
	 * @var bool|string
	 */
	var $field_name = false;

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
	 * @var bool|int
	 */
	protected $_field_index = false;

	/**
	 * @var null|mixed
	 */
	protected $_value = null;

	/**
	 * Returns an array of delegate properties and with their $args prefix for this class.
	 *
	 * @return array
	 */
	static function DELEGATES() {

		return array(
			'view'    => 'view',
			'storage' => 'storage',
		);

	}


	/**
	 * @return array|void
	 */
	function get_delegates() {

		return array_merge(
			self::DELEGATES(),
			$this->get_view_delegates()
		);

	}

	/**
	 * @return array
	 */
	static function TRANSFORMS() {

		/**
		 * Create a regex to insure delegated and no_prefix $args are not matched
		 * nor are $args that contain underscores.
		 *
		 * @see http://stackoverflow.com/a/5334825/102699 for 'not' regex logic
		 * @see http://www.rexegg.com/regex-lookarounds.html for negative lookaheads
		 * @see http://ocpsoft.org/tutorials/regular-expressions/and-in-regex/ for logical 'and'
		 * @see http://www.regular-expressions.info/refadv.html for "Keep text out of the regex match"
		 *
		 * @example Regex if 'foo' and 'bar' are no_prefix or delegates:
		 *
		 *  '^(?!(?|foo|bar)$)(?!.*_)(.*)'
		 *
		 * @note Other similar regex that might work the same
		 *
		 *  '^(?!foo$)(?!bar$)(?!.*_)(.*)';
		 *  '^(?!(?>(foo|bar))$)(?!.*_)(.*)'
		 *  '^(?!\K(foo|bar)$)(?!.*_)(.*)'
		 *
		 * Example matches any string except 'foo', 'bar' or one containing an underscore ('_').
		 */
		$html_attributes = array_merge( self::NO_PREFIX(), self::DELEGATES() );
		$html_attributes = '^(?!(?|' . implode( '|', $html_attributes ) . ')$)(?!.*_)(.*)$';

		return array(
			'^label$'                           => 'label_text',
			$html_attributes                    => 'html_$1',
			'^input_([^_]+)$'                   => 'input_html_$1',
			'^html_([^_]+)$'                    => 'input_html_$1',
			'^input_wrapper_([^_]+)$'           => 'input_wrapper_html_$1',
			'^wrapper_([^_]+)$'                 => 'input_wrapper_html_$1',
			'(?:^|_)wrapper(_wrapper)+(?:_|$)'  => 'wrapper',
			'(?:^|_)html(_html)+(?:_|$)'        => 'html',
		);

	}

	/**
	 * Array of field names that should not get a prefix.
	 *
	 * Intended to be used by subclasses.
	 *
	 * @return array
	 */
	static function NO_PREFIX() {

		return array(
			'value',
		);

	}

	/**
	 * @param string $field_name
	 * @param array $field_args
	 */
	function __construct( $field_name, $field_args = array() ) {

		$field_args[ 'field_name' ] = $field_name;

		parent::__construct( $field_args );

	}

	/**
	 * @param array $field_args
	 *
	 * @return array
	 */
	function pre_delegate_args( $field_args ) {

		if ( !empty( $field_args[ 'view' ] ) ) {
			$this->view = $field_args[ 'view' ];

			unset( $field_args[ 'view' ] );

		} else {

		 	$this->view = 'default';
		}

		return $field_args;

	}

	/**
	 * Register the default view for this class.
	 */
	function initialize_class() {

		WP_Metadata::register_field_view( 'default', 'WP_Field_View' );

	}

	function initialize( $field_args ) {

		/**
		 * @todo Update to instantiate via storage factory.
		 */
		$this->storage = new WP_Meta_Storage( $this, $this->get_storage_args() );

		$this->set_field_view( $this->view, $this->get_view_args() );

	}

	/**
	 * @return array
	 */
	function get_storage_args() {

		return $this->delegated_args[ 'storage' ];

	}

	/**
	 * @return array
	 */
	function get_view_args() {

		return array_merge(
			$this->delegated_args[ 'view' ],
			WP_Metadata::extract_prefixed_args( $this->args, $this->get_view_delegates()
		));

	}

	/**
	 * @return array
	 */
	function get_view_delegates() {

		$delegates = call_user_func( array( $this->get_view_class(), 'DELEGATES' ) );
		$delegates[ 'view' ] = 'view';

		return $delegates;

	}

	/**
	 * @param string $view_name
	 * @param array $view_args
	 */
	function set_field_view( $view_name, $view_args = array() ) {

		if ( ! WP_Metadata::field_view_exists( $view_name ) ) {
			$this->view = false;
		} else {
			$view_args[ 'view_name' ] = $view_name;
			$view_args[ 'field' ] = $this; // This is redundant, but that's okay
			$this->view = $this->make_field_view( $view_name, $view_args );
		}

	}

	/**
	 * @param string $feature_type
	 * @param array $feature_args
	 *
	 * @return null|WP_Field_Feature_Base
	 */
	function make_field_feature( $feature_type, $feature_args ) {

		if ( $feature_class = WP_Metadata::get_feature_type( $feature_type ) ) {
			$feature = new $feature_class( $this, $feature_args );
		} else {
			$feature = null;
		}

		return $feature;

	}

	/**
	 * @param string $view_name
	 * @param array $view_args
	 *
	 * @return WP_Field_View
	 */
	function make_field_view( $view_name, $view_args = array() ) {

		$field_view_class = $this->get_view_class( $view_name );
		$view = new $field_view_class( $view_name, $view_args );
		$view->field = $this; // This is redundant, but that's okay

		return $view;

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

		return is_object( $this->view ) ? get_class( $this->view ) : WP_Metadata::get_view_class( 'field', $view_name, get_class( $this ) );

	}

	/**
	 *
	 */
	function value() {

		if ( is_null( $this->_value ) && $this->has_storage() ) {
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

	/**
	 * @param null|mixed $value
	 */
	function update_value( $value = null ) {

		if ( ! is_null( $value ) ) {
			$this->set_value( $value );
		}
		if ( $this->has_storage() ) {
			$this->storage->update_value( $this->storage_key(), $this->value() );
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
	 * @return mixed
	 */
	function __set( $property_name, $value ) {

		return property_exists( $this->view, $property_name ) ? $this->view->$property_name = $value : null;

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
