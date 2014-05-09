<?php
/**
 * Plugin Name: #metadata
 * Description: Feature-as-a-plugin offering Forms & Fields for WordPress, initially forms for post admin edit but later for users, comments, taxonomy terms, options, etc.
 */

/**
 * @todo - Add an autoloader for most of these:
 */
require( dirname( __FILE__ ) . '/functions.php' );
require( dirname( __FILE__ ) . '/functions/post.php' );
require( dirname( __FILE__ ) . '/functions/user.php' );
require( dirname( __FILE__ ) . '/functions/comment.php' );
require( dirname( __FILE__ ) . '/functions/option.php' );

require( dirname( __FILE__ ) . '/base/class-metadata-base.php' );

require( dirname( __FILE__ ) . '/core/class-object-type.php' );
require( dirname( __FILE__ ) . '/core/class-html-element.php' );
require( dirname( __FILE__ ) . '/core/class-registry.php' );

require( dirname( __FILE__ ) . '/base/class-storage-base.php' );
require( dirname( __FILE__ ) . '/base/class-field-base.php' );
require( dirname( __FILE__ ) . '/base/class-field-feature-base.php' );
require( dirname( __FILE__ ) . '/base/class-form-view-base.php' );
require( dirname( __FILE__ ) . '/base/class-field-view-base.php' );

require( dirname( __FILE__ ) . '/storage/class-core-storage.php' );
require( dirname( __FILE__ ) . '/storage/class-meta-storage.php' );
require( dirname( __FILE__ ) . '/storage/class-option-storage.php' );

require( dirname( __FILE__ ) . '/forms/class-form.php' );

require( dirname( __FILE__ ) . '/fields/class-text-field.php' );
require( dirname( __FILE__ ) . '/fields/class-textarea-field.php' );
require( dirname( __FILE__ ) . '/fields/class-url-field.php' );
require( dirname( __FILE__ ) . '/fields/class-date-field.php' );

require( dirname( __FILE__ ) . '/features/class-field-input-feature.php' );
require( dirname( __FILE__ ) . '/features/class-field-label-feature.php' );
require( dirname( __FILE__ ) . '/features/class-field-help-feature.php' );
require( dirname( __FILE__ ) . '/features/class-field-message-feature.php' );
require( dirname( __FILE__ ) . '/features/class-field-infobox-feature.php' );

require( dirname( __FILE__ ) . '/views/class-form-view.php' );
require( dirname( __FILE__ ) . '/views/class-field-view.php' );

/**
 * Class WP_Metadata
 */
class WP_Metadata {

	/**
	 * @var string
	 */
	static $prefix = false;

	/**
	 * @var array
	 */
	private static $_form_args = array();

	/**
	 * @var array
	 */
	private static $_field_args = array();

	/**
	 * @var array
	 */
	private static $_object_type_fields = array();

	/**
	 * @var array
	 */
	private static $_object_type_forms = array();

	/**
	 * @var array
	 */
	private static $_field_types = array();

	/**
	 * @var WP_Registry
	 */
	private static $_feature_type_registry = array();

	/**
	 * @var array
	 */
	private static $_storages = array();

	/**
	 * @var array
	 */
	private static $_element_attributes = array();

	/**
	 * @var array
	 */
	private static $_views = array();

	/**
	 *
	 */
	static function on_load() {

		//    self::$_object_type_field_registry = new WP_Registry();
		//    self::$_object_type_form_registry = new WP_Registry();
		//    self::$_view_registry = new WP_Registry();

		/*
		 * Register field classes
		 */
		//self::$_field_type_registry = new WP_Registry();
		self::register_field_type( 'text', 'WP_Text_Field' );
		self::register_field_type( 'textarea', 'WP_TextArea_Field' );
		self::register_field_type( 'url', 'WP_Url_Field' );
		self::register_field_type( 'date', 'WP_Date_Field' );

		self::initialize_feature_type_registry();
		self::register_feature_type( 'input', 'WP_Field_Input_Feature' );
		self::register_feature_type( 'label', 'WP_Field_Label_Feature' );
		self::register_feature_type( 'message', 'WP_Field_Message_Feature' );
		self::register_feature_type( 'help', 'WP_Field_Help_Feature' );
		self::register_feature_type( 'infobox', 'WP_Field_Infobox_Feature' );

		/*
		 * Register "storage" classes
		 */
		//self::$_storage_registry = new WP_Registry();
		self::register_storage( 'meta', 'WP_Meta_Storage' );
		self::register_storage( 'core', 'WP_Core_Storage' );
		self::register_storage( 'option', 'WP_Option_Storage' );
		self::register_storage( 'taxonomy', 'WP_Taxonomy_Storage' );

		//    /**
		//     * Hook a different hook differently based on how the page is loaded to initialize the fields.
		//     */
		//    if ( defined( 'DOING_AJAX' ) ) {
		//      add_action( 'admin_init', array( __CLASS__, '_wp_loaded' ) );
		//    } else if ( is_admin() ) {
		//      add_action( 'admin_menu', array( __CLASS__, '_wp_loaded' ) );
		//    } else {
		//      add_action( 'wp_loaded', array( __CLASS__, '_wp_loaded' ) );
		//    }

		add_action( 'registered_post_type', array( __CLASS__, '_registered_post_type' ), 10, 2 );

		if ( is_admin() ) {
			add_action( 'edit_form_top', array( __CLASS__, '_edit_post_form' ) );
			add_action( 'edit_form_after_title', array( __CLASS__, '_edit_post_form' ) );
			add_action( 'edit_form_after_editor', array( __CLASS__, '_edit_post_form' ) );
			add_action( 'edit_form_advanced', array( __CLASS__, '_edit_post_form' ) );

			// @todo Add action / filter for Custom Fields meta box integration
			// See https://github.com/wordpress-metadata/metadata-ui-api/issues/18
		}

	}

	/**
	 * @param string $post_type
	 * @param array $args
	 */
	static function _registered_post_type( $post_type, $args ) {

		global $wp_post_types;

		if ( empty( $wp_post_types[ $post_type ] ) ) {
			return;
		}

		$wp_post_types[ $post_type ]->default_form = !empty( $args->default_form ) ? $args->default_form : 'after_title';

	}

	/**
	 * Hook handler for 'edit_form_top', 'edit_form_after_title'. 'edit_form_after_editor' and 'edit_form_advanced'.
	 *
	 * Displayed the post_type's default form based on the value of post_type_object->default_form that can be set
	 * as an argument to register_post_type. Valid values for default form include:
	 *
	 *    'top', 'after_title', 'after_editor', 'advanced', or 'custom_fields'
	 *
	 * @todo Explain how to handle custom metaboxes once we figure out how we'll handle them.
	 *
	 * @param WP_Post $post
	 *
	 * @internal
	 *
	 */
	static function _edit_post_form( $post ) {

		$post_type = $post->post_type;
		$object_type = wp_get_post_object_type( $post_type );
		$current_form = preg_replace( '#^edit_form_(.*)$#', '$1', current_action() );

		if ( $current_form == get_post_type_object( $post_type )->default_form ) {
			if ( !self::form_registered( $current_form, $object_type ) ) {
				self::register_form( $current_form, $object_type );
			}

			$form = self::get_form( $current_form, $object_type );

			$form->set_storage_object( $post );
			$form->the_form();
		}

	}

	/**
	 * @param string $object_type
	 *
	 * @return array
	 */
	static function get_field_names( $object_type ) {

		$object_type = (string) $object_type;

		return isset( self::$_object_type_fields[ $object_type ] ) ? array_keys( self::$_object_type_fields[ $object_type ] ) : array();

	}

	/**
	 * Retrieve a field
	 *
	 * @param string $field_name
	 * @param string|WP_Object_Type $object_type
	 * @param array $field_args
	 *
	 * @return WP_Field_Base
	 */
	static function get_field( $field_name, $object_type, $field_args = array() ) {

		$field_index = self::get_field_index( $field_name, $object_type );
		$field_args = wp_parse_args( $field_args, self::get_field_args( $field_index ) );
		$field = self::make_field( $field_name, $object_type, $field_args );

		return $field;

	}

	/**
	 * @param int $field_index
	 *
	 * @return bool|array
	 */
	static function get_field_args( $field_index ) {

		return isset( self::$_field_args[ $field_index ] ) ? self::$_field_args[ $field_index ] : false;

	}

	/**
	 * Retrieve a field
	 *
	 * @param string $field_name
	 * @param string|WP_Object_Type $object_type
	 *
	 * @return int
	 */
	static function get_field_index( $field_name, $object_type ) {

		$object_type = (string) $object_type;

		return isset( self::$_object_type_fields[ $object_type ][ $field_name ] ) ? self::$_object_type_fields[ $object_type ][ $field_name ] : false;

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
	static function make_field( $field_name, $object_type, $field_args = array() ) {

		$field = false;

		if ( !isset( $field_args[ 'field_type' ] ) ) {
			/*
			 * We have to do this normalization of the 'type' $arg prior to
			 * the Field classes __construct() because it drives the class used
			 * to instantiate the Field. All other $args can be normalized
			 * in the Field class constructor.
			 */
			if ( !isset( $field_args[ 'type' ] ) ) {
				$field_args[ 'field_type' ] = 'text';
			}
			else {
				$field_args[ 'field_type' ] = $field_args[ 'type' ];

				unset( $field_args[ 'type' ] );
			}
		}

		/**
		 * @var string|object $field_type If string, a class. If object a filepath to load a class and $args
		 */
		$field_type = self::_get_field_type( $field_args[ 'field_type' ] );

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

		}
		elseif ( is_array( $field_type ) ) {
			/**
			 * Field type is a 'Prototype'
			 */
			$field_args = wp_parse_args( $field_args, $field_type );
			$field = self::make_field( $field_name, $object_type, $field_args );
		}

		return $field;

	}

	/**
	 * @param string $field_type
	 *
	 * @return string|array|object
	 */
	private static function _get_field_type( $field_type ) {

		return self::$_field_types[ $field_type ];

	}

	/**
	 * @param string $form_name
	 * @param string|WP_Object_Type $object_type
	 *
	 * @return bool
	 */
	static function form_registered( $form_name, $object_type ) {

		return false !== self::get_form_index( $form_name, $object_type );

	}

	/**
	 * Retrieve a form
	 *
	 * @param string $form_name
	 * @param string|WP_Object_Type $object_type
	 * @param array $form_args
	 *
	 * @return WP_Form
	 */
	static function get_form( $form_name, $object_type, $form_args = array() ) {

		$form_index = self::get_form_index( $form_name, $object_type );
		$form_args = wp_parse_args( $form_args, self::get_form_args( $form_index ) );
		$form = self::make_form( $form_name, $object_type, $form_args );

		return $form;

	}

	/**
	 * @param int $form_index
	 *
	 * @return bool|array
	 */
	static function get_form_args( $form_index ) {

		return isset( self::$_form_args[ $form_index ] ) ? self::$_form_args[ $form_index ] : false;

	}

	/**
	 * Retrieve a form
	 *
	 * @param string $form_name
	 * @param string|WP_Object_Type $object_type
	 *
	 * @return int
	 */
	static function get_form_index( $form_name, $object_type ) {

		return isset( self::$_object_type_forms[ $object_type ][ $form_name ] ) ? self::$_object_type_forms[ $object_type ][ $form_name ] : false;

	}

	/**
	 * @param string $form_name
	 * @param string $object_type
	 * @param array $form_args
	 *
	 * @return WP_Form
	 */
	static function make_form( $form_name, $object_type, $form_args = array() ) {

		/**
		 * @todo Support more than one type of form. Maybe. If needed.
		 */
		$form = new WP_Form( $form_name, $object_type, $form_args );

		return $form;

	}

	/**
	 * @param string $field_name
	 * @param string $object_type
	 * @param array $field_args
	 *
	 * @return int Field Index
	 */
	static function register_field( $field_name, $object_type, $field_args = array() ) {

		$field_args[ 'field_name' ] = $field_name;
		$field_args[ 'object_type' ] = $object_type;
		$field_args[ 'field_index' ] = count( self::$_field_args );

		self::$_object_type_fields[ $object_type ][ $field_name ] = $field_args[ 'field_index' ];
		self::$_field_args[] = $field_args;

		return $field_args[ 'field_index' ];

	}

	/**
	 * @param string $form_name
	 * @param string $object_type
	 * @param array $form_args
	 *
	 * @return int Form Index
	 */
	static function register_form( $form_name, $object_type, $form_args = array() ) {

		$form_args[ 'form_name' ] = $form_name;
		$form_args[ 'object_type' ] = $object_type;
		$form_args[ 'form_index' ] = count( self::$_form_args );

		self::$_object_type_forms[ $object_type ][ $form_name ] = $form_args[ 'form_index' ];
		self::$_form_args[] = $form_args;

		return $form_args[ 'form_index' ];

	}

	/**
	 * @param string $type_name - Name of type
	 * @param string|array $type_args - Classname or Array of $args
	 */
	static function register_field_type( $type_name, $type_args = array() ) {

		self::$_field_types[ $type_name ] = $type_args;

	}

	/**
	 * @param string $storage_name - Name of storage
	 * @param bool|string $storage_args - Classname
	 */
	static function register_storage( $storage_name, $storage_args = false ) {

		self::$_storages[ $storage_name ] = $storage_args;

	}

	/**
	 * @param string $tag_name
	 * @param array $attributes
	 * @param mixed $value
	 *
	 * @return WP_Html_Element
	 */
	static function get_element_html( $tag_name, $attributes, $value ) {

		$html_element = self::get_html_element( $tag_name, $attributes, $value, true );

		return $html_element->get_element_html();

	}

	/**
	 * @param string $tag_name
	 * @param array $attributes
	 * @param null ,mixed $value
	 * @param bool $reuse
	 *
	 * @return WP_Html_Element
	 */
	static function get_html_element( $tag_name, $attributes = array(), $value = null, $reuse = false ) {

		if ( !$reuse ) {
			$element = new WP_Html_Element( $tag_name, $attributes, $value );
		}
		else {
			/**
			 * @var WP_Html_Element $reusable_element
			 */
			static $reusable_element = false;
			if ( !$reusable_element ) {
				$reusable_element = new WP_Html_Element( $tag_name, $attributes, $value );
			}
			else {
				$reusable_element->reset_element( $tag_name, $attributes, $value );
			}
			$element = $reusable_element;
		}

		return $element;

	}

	/**
	 * @param $html_element
	 *
	 * @return array
	 */
	static function get_html_attributes( $html_element ) {

		if ( !isset( self::$_element_attributes[ $html_element ] ) ) {

			/**
			 * @see http://www.w3.org/TR/html5/dom.html#global-attributes
			 */
			$attributes = array(
				'accesskey',
				'class',
				'contenteditable',
				'dir',
				'draggable',
				'dropzone',
				'hidden',
				'id',
				'lang',
				'spellcheck',
				'style',
				'tabindex',
				'title',
				'translate'
			);

			switch ( $html_element ) {

				case 'input':
					$more_attributes = array(
						'accept',
						'alt',
						'autocomplete',
						'autofocus',
						'autosave',
						'checked',
						'dirname',
						'disabled',
						'form',
						'formaction',
						'formenctype',
						'formmethod',
						'formnovalidate',
						'formtarget',
						'height',
						'inputmode',
						'list',
						'max',
						'maxlength',
						'min',
						'minlength',
						'multiple',
						'name',
						'pattern',
						'placeholder',
						'readonly',
						'required',
						'selectionDirection',
						'size',
						'src',
						'step',
						'type',
						'value',
						'width'
					);
					break;

				case 'textarea':
					$more_attributes = array( 'cols', 'name', 'rows', 'tabindex', 'wrap' );
					break;

				case 'label':
					$more_attributes = array( 'for', 'form' );
					break;

				case 'ul':
					$more_attributes = array( 'compact', 'type' );
					break;

				case 'ol':
					$more_attributes = array( 'compact', 'reversed', 'start', 'type' );
					break;

				case 'li':
					$more_attributes = array( 'type', 'value' );
					break;

				case 'a':
					$more_attributes = array(
						'charset',
						'coords',
						'download',
						'href',
						'hreflang',
						'media',
						'rel',
						'target',
						'type'
					);
					break;

				case 'section':
				case 'div':
				case 'span':
				default:
					$more_attributes = false;
					break;
			}

			if ( $more_attributes ) {
				$attributes = array_merge( $attributes, $more_attributes );
			}

			self::$_element_attributes[ $html_element ] = array_fill_keys( $attributes, false );

		}

		return self::$_element_attributes[ $html_element ];

	}

	/**
	 * Register a class to be used as a field_view for the current class.
	 *
	 * $wp_field->register_field_view( 'default', 'WP_Field_View' );
	 *
	 * @param string $view_name The name of the view that is unique for this class.
	 * @param string $view_type Type of view
	 * @param string $class_name The class name for the View object.
	 * @param string $associated_class
	 */
	static function register_view( $view_type, $view_name, $class_name, $associated_class ) {

		if ( !self::view_exists( $view_name, $view_type, $associated_class ) ) {
			self::$_views[ $view_type ][ $associated_class ][ $view_name ] = $class_name;
		}

	}

	/**
	 * Does the named field view exist
	 *
	 * @param string $view_name The name of the view that is unique for this class.
	 * @param string $view_type Type of view
	 * @param string $associated_class
	 *
	 * @return bool
	 */
	static function view_exists( $view_type, $view_name, $associated_class ) {

		return isset( self::$_views[ $view_type ][ $associated_class ][ $view_name ] );

	}

	/**
	 * Retrieve the class name for a named view.
	 *
	 * @param string $view_type Type of view
	 * @param string $view_name The name of the view that is unique for this class.
	 * @param string $associated_class
	 *
	 * @return string
	 */
	static function get_view_class( $view_type, $view_name, $associated_class ) {

		return self::view_exists( $view_type, $view_name, $associated_class ) ? self::$_views[ $view_type ][ $associated_class ][ $view_name ] : false;

	}

	/*********************************************/
	/***  Field Feature Type Registry Methods  ***/
	/*********************************************/

	/**
	 *
	 */
	static function initialize_feature_type_registry() {

		self::$_feature_type_registry = new WP_Registry();

	}

	/**
	 * @param string $feature_type Name of Feature
	 * @param string $feature_class Classname
	 */
	static function register_feature_type( $feature_type, $feature_class ) {

		self::$_feature_type_registry->register_item( $feature_type, $feature_class );

	}

	static function get_feature_type( $feature_type ) {

		return self::$_feature_type_registry->get_item( $feature_type );

	}

	/*********************************************/
	/*** Prefix related methods                ***/
	/*********************************************/
	/**
	 * Extract args with specified prefixes.
	 *
	 * Look for $args based on their prefixes (i.e. 'html_').
	 * If found capture the non-prefixed key and value into $extracted_args for return.
	 * (Stripping the prefix allows for nested values, i.e. 'label_html_class')
	 *
	 * @note: If a string prefix is passed a single dimensional array is returned.
	 *        If an array of  prefixes is passed a two dimensional array is returned.
	 *
	 * @param array $prefixed_args
	 * @param string|array $prefixes
	 * @param array $args
	 *
	 * @return mixed
	 */
	static function extract_prefixed_args( $prefixed_args, $prefixes, $args = array() ) {

		$extracted_args = array();

		$args = wp_parse_args( $args, array(
			'strip_prefix' => true,
		) );

		if ( is_string( $original = $prefixes ) ) {
			$prefixes = array( $prefixes => $prefixes );
		}

		if ( is_array( $prefixes ) && count( $prefixes ) ) {
			$extracted_args = array_fill_keys( array_keys( $prefixes ), array() );
			$match_regex = '#^(' . implode( '|', $prefixes ) . ')_(.*)$#';
			$delegate_args = array();

			foreach ( $prefixed_args as $arg_name => $arg_value ) {
				if ( preg_match( $match_regex, $arg_name, $match ) ) {
					if ( $args[ 'strip_prefix' ] || 2 <= substr_count( $arg_name, '_' ) ) {
						$extracted_args[ $match[ 1 ] ][ $match[ 2 ] ] = $arg_value;
					}
					else {
						$extracted_args[ $match[ 1 ] ][ $arg_name ] = $arg_value;
					}
				}
			}
		}

		return is_string( $original ) ? $extracted_args[ $original ] : $extracted_args;

	}

	static function strip_arg_prefixes( $prefixed_args, $prefixes ) {

	}

}

WP_Metadata::on_load();