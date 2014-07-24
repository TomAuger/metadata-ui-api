<?php
/**
 * Plugin Name: #metadata
 * Description: Feature-as-a-plugin offering Forms & Fields for WordPress, initially forms for post admin edit but later for users, comments, taxonomy terms, options, etc.
 */

require( dirname( __FILE__ ) . '/functions.php' );
require( dirname( __FILE__ ) . '/functions/post.php' );
require( dirname( __FILE__ ) . '/functions/user.php' );
require( dirname( __FILE__ ) . '/functions/comment.php' );
require( dirname( __FILE__ ) . '/functions/option.php' );

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
	 * @var array
	 */
	private static $_element_attributes = array();

	/**
	 * @var array
	 */
	private static $_views = array();

	/**
	 * @var WP_Registry
	 */
	private static $_feature_type_registry = array();

	/**
	 * @var WP_Registry
	 */
	private static $_storage_type_registry = array();

  /**
   * @var WP_Registry
   */
  private static $_object_factory_registry = array();


  private static $_class_annotations;

  /**
	 * @var array
	 */
	private static $_autoload_classes = array(
    'WP_Object_Type'           => 'core/class-object-type.php',
    'WP_Object_Factory'        => 'core/class-object-factory.php',
		'WP_Html_Element'          => 'core/class-html-element.php',
		'WP_Registry'              => 'core/class-registry.php',
		'WP_Annotated_Property'    => 'core/class-annotated-property.php',
		'WP_Metadata_Base'         => 'base/class-metadata-base.php',
		'WP_Storage_Base'          => 'base/class-storage-base.php',
		'WP_Field_Base'            => 'base/class-field-base.php',
		'WP_Field_Feature_Base'    => 'base/class-field-feature-base.php',
		'WP_Form_View_Base'        => 'base/class-form-view-base.php',
		'WP_Field_View_Base'       => 'base/class-field-view-base.php',
		'WP_Core_Storage'          => 'storage/class-core-storage.php',
		'WP_Meta_Storage'          => 'storage/class-meta-storage.php',
		'WP_Option_Storage'        => 'storage/class-option-storage.php',
		'WP_Memory_Storage'        => 'storage/class-memory-storage.php',
		'WP_Form'                  => 'forms/class-form.php',
		'WP_Text_Field'            => 'fields/class-text-field.php',
		'WP_Textarea_Field'        => 'fields/class-textarea-field.php',
		'WP_Url_Field'             => 'fields/class-url-field.php',
		'WP_Date_Field'            => 'fields/class-date-field.php',
		'WP_Hidden_Field'          => 'fields/class-hidden-field.php',
		'WP_Field_Input_Feature'   => 'features/class-field-input-feature.php',
		'WP_Field_Label_Feature'   => 'features/class-field-label-feature.php',
		'WP_Field_Help_Feature'    => 'features/class-field-help-feature.php',
		'WP_Field_Message_Feature' => 'features/class-field-message-feature.php',
		'WP_Field_Infobox_Feature' => 'features/class-field-infobox-feature.php',
		'WP_Form_View'             => 'views/class-form-view.php',
		'WP_Field_View'            => 'views/class-field-view.php',
		'WP_Hidden_Field_View'     => 'views/class-hidden-field-view.php',
	);


	/**
	 *
	 */
	static function on_load() {

		spl_autoload_register( array( __CLASS__, '_autoloader' ) );

		//    self::$_object_type_field_registry = new WP_Registry();
		//    self::$_object_type_form_registry = new WP_Registry();
		//    self::$_view_registry = new WP_Registry();

		/*
		 * Register field classes
		 */
		//self::$_field_type_registry = new WP_Registry();
		self::register_field_type( 'text', 'WP_Text_Field' );
		self::register_field_type( 'textarea', 'WP_Textarea_Field' );
		self::register_field_type( 'url', 'WP_Url_Field' );
		self::register_field_type( 'date', 'WP_Date_Field' );
		self::register_field_type( 'hidden', 'WP_Hidden_Field' );

		self::register_field_view( 'hidden', 'WP_Hidden_Field_View' );

		self::initialize_feature_type_registry();
		self::register_feature_type( 'input', 'WP_Field_Input_Feature' );
		self::register_feature_type( 'label', 'WP_Field_Label_Feature' );
		self::register_feature_type( 'message', 'WP_Field_Message_Feature' );
		self::register_feature_type( 'help', 'WP_Field_Help_Feature' );
		self::register_feature_type( 'infobox', 'WP_Field_Infobox_Feature' );

		/*
		 * Register "storage" classes
		 */
		self::initialize_storage_type_registry();
		self::register_storage_type( 'meta', 'WP_Meta_Storage' );
		self::register_storage_type( 'core', 'WP_Core_Storage' );
		self::register_storage_type( 'option', 'WP_Option_Storage' );
		self::register_storage_type( 'taxonomy', 'WP_Taxonomy_Storage' );
		self::register_storage_type( 'memory', 'WP_Memory_Storage' );

		/*
		 * Register "factory" classes
		 */
		self::initialize_object_factory_registry();
		self::register_object_factory( 'field',   array( __CLASS__, 'make_field' )  );
    self::register_object_factory( 'storage', array( __CLASS__, 'make_storage' )  );
		self::register_object_factory( 'form',    array( __CLASS__, 'make_form' ) );
    self::register_object_factory( 'html_element', 'WP_Html_Element' );


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
			add_action( 'admin_init', array( __CLASS__, '_admin_init' ) );
		}

	}

	/**
	 * @param string $class_name
	 * @param string $class_filepath
	 * @return bool Return true if it was registered, false if not.
	 */
	static function register_autoload_class( $class_name, $class_filepath ) {

		if ( ! isset( self::$_autoload_classes[ $class_name ] ) ) {

			self::$_autoload_classes[$class_name] = $class_filepath;

			return true;

		}

		return false;

	}

	/**
	 * @param string $class_name
	 */
	static function _autoloader( $class_name ) {

		if ( isset( self::$_autoload_classes[ $class_name ] ) ) {

			$filepath = self::$_autoload_classes[$class_name];

			/**
			 * @todo This needs to be made to work for Windows...
			 */
			if ( '/' == $filepath[0] ) {

				require_once( $filepath );

			} else {

				require_once( dirname( __FILE__ ) . "/{$filepath}" );

			}

		}

	}

	/**
	 *
	 */
	static function _admin_init() {
		if ( WP_Metadata::is_post_edit_screen() ) {
			add_action( 'edit_form_top', array( __CLASS__, '_edit_post_form' ) );
			add_action( 'edit_form_after_title', array( __CLASS__, '_edit_post_form' ) );
			add_action( 'edit_form_after_editor', array( __CLASS__, '_edit_post_form' ) );
			add_action( 'edit_form_advanced', array( __CLASS__, '_edit_post_form' ) );

//			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );

			add_action( 'save_post_' . self::get_current_screen()->post_type, array( __CLASS__, '_save_post' ), 10, 3 );

			// Add global styles for metadata api.
			add_action( 'admin_enqueue_scripts', array( __CLASS__, '_enqueue_admin_styles' ) );
		}
	}

	/**
     * Load css required for the metadata api.
     *
     */
	static function _enqueue_admin_styles( $hook ) {

		wp_enqueue_style( 'metadata', plugin_dir_url( __FILE__ ) . 'css/metadata.css', array() );

	}

	/**
	 * @return bool
	 *
	 * @todo For Core dev review. Better way?
	 */
	static function is_post_edit_screen() {

		global $pagenow;
		return 'post.php' == $pagenow || 'post-new.php' == $pagenow;

	}

	/**
	 * Grabs the current or a new WP_screen object.
	 *
	 * Tries to get the current one but if it's not available then it hacks it's way to recreate one
	 * because WordPress does not consistently set it, and it's not our place to change it's state.
	 * We just want what we want.
	 *
	 * @return WP_Screen
	 *
	 * @todo For Core dev review. Better way?
	 */
	static function get_current_screen() {

		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			global $hook_suffix, $page_hook, $plugin_page, $pagenow, $current_screen;
			if ( empty( $hook_suffix ) ) {
				$save_hook_suffix    = $hook_suffix;
				$save_current_screen = $current_screen;
				if ( isset( $page_hook ) ) {
					$hook_suffix = $page_hook;
				} else if ( isset( $plugin_page ) ) {
					$hook_suffix = $plugin_page;
				} else if ( isset( $pagenow ) ) {
					$hook_suffix = $pagenow;
				}
				set_current_screen();
				$screen         = get_current_screen();
				$hook_suffix    = $save_hook_suffix;
				$current_screen = $save_current_screen;
			}
		}

		return $screen;
	}

	/**
	 * @param string|WP_Object_Type $object_type
	 * @param bool|array $form_names
	 *
	 * @return array
	 */
	static function get_forms( $object_type, $form_names = false ) {
		$forms = array();

		if ( isset( self::$_object_type_forms[$object_type] ) ) {
			$forms = self::$_object_type_forms[ $object_type ];
		}

		if ( $form_names ) {
			if ( is_array( $form_names ) ) {
				$form_names = array_flip( $form_names );
			} else {
				$form_names = array( $form_names => 0 );
			}
			$forms = array_intersect_key( $forms, $form_names );
		}

		foreach( $forms as $form_name => $form_args ) {
			$forms[$form_name] = self::make_form( $form_name, $object_type, $form_args );
		}

		return $forms;
	}

	/**
	 * @param string $post_type
	 * @return array
	 */
	static function get_forms_from_POST( $post_type ) {
		$forms = array();
		if ( ! isset( $_POST['wp_metadata_forms'] ) || ! is_array( $_POST['wp_metadata_forms'] ) ) {
			$forms = array();
		} else {
			$forms = $_POST['wp_metadata_forms'];
			foreach( $forms as $form_name => $form_data ) {
				$form = self::make_form( $form_name, wp_get_post_object_type( $post_type ), array( 'view' => false ) );
				/**
				 * @var WP_Field_Base $field
				 */
				foreach( $form->fields as $field_name => $field ) {
					if ( isset( $form_data[$field_name] ) ) {
						$field->set_value( $form_data[$field_name] );
					}
				}
				$forms[$form_name] = $form;
			}
		}
		return $forms;
	}

	/**
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param bool $update
	 */
	static function _save_post( $post_id, $post, $update ) {
		if ( count( $forms = self::get_forms_from_POST( $post->post_type ) ) ) {
			/**
			 * @var WP_Form $form
			 */
			foreach ( $forms as $form_name => $form ) {
				$form->set_object( $post );
				$form->update_values();
			}
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
	 * @param string|WP_Object_Type $object_type
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

			} else {

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

		} else if ( is_array( $field_type ) ) {

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
	 * @param string|WP_Object_Type $object_type
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
	 * @param string|WP_Object_Type $object_type
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
	 * @param string|WP_Object_Type $object_type
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
	 * @param string|array $type_def - Classname, or array of $args
	 * @return bool Whether the object type $type_name was registered
	 */
	static function register_field_type( $type_name, $type_def = array() ) {

		if ( ! isset( self::$_field_types[ $type_name ] ) ) {

			self::$_field_types[ $type_name ] = $type_def;

			return true;
		}

		return false;
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
	 * Register a class to be used as a view for the current class.
	 *
	 * @example
	 *
	 *      WP_Metadata::register_view( 'field', 'default', 'WP_Field_View' );
	 *      WP_Metadata::register_view( 'field', 'hidden', 'WP_Hidden_Field_View' );
	 *
	 * @param string $view_type Type of view
	 * @param string $view_name The name of the view that is unique for this class.
	 * @param string $class_name The class name for the View object.
	 */
	static function register_view( $view_type, $view_name, $class_name ) {

		if ( !self::view_exists( $view_name, $view_type ) ) {
			self::$_views[ $view_type ][ $view_name ] = $class_name;
		}

	}

	/**
	 * Does the named field view exist
	 *
	 * @param string $view_name The name of the view that is unique for this class.
	 * @param string $view_type Type of view
	 *
	 * @return bool
	 */
	static function view_exists( $view_type, $view_name ) {

		return isset( self::$_views[ $view_type ][ $view_name ] );

	}

	/**
	 * Retrieve the class name for a named view.
	 *
	 * @param string $view_type Type of view
	 * @param string $view_name The name of the view that is unique for this class.
	 *
	 * @return string
	 */
	static function get_view_class( $view_type, $view_name ) {

		return self::view_exists( $view_type, $view_name ) ? self::$_views[ $view_type ][ $view_name ] : false;

	}

	/**
	 * Register a class to be used as a view for the current class.
	 *
	 * @example
	 *
	 *      WP_Metadata::register_field_view( 'default', 'WP_Field_View' );
	 *      WP_Metadata::register_field_view( 'hidden', 'WP_Hidden_Field_View' );
	 *
	 * @param string $view_name The name of the view that is unique for this class.
	 * @param string $class_name The class name for the View object.
	 */
	static function register_field_view( $view_name, $class_name ) {

		self::register_view( 'field', $view_name, $class_name );

	}

	/**
	 * Does the named field view exist?
	 *
	 * @param string $view_name The name of the view that is unique for this class.
	 *
	 * @return bool
	 */
	static function field_view_exists( $view_name ) {

		return self::view_exists( 'field', $view_name );

	}

	/**
	 * Retrieve the class name for a named view.
	 *
	 * @param string $view_name The name of the view that is unique for this class.
	 *
	 * @return string
	 */
	static function get_field_view_class( $view_name ) {

		return self::get_view_class( 'field', $view_name );

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

		self::$_feature_type_registry->register_entry( $feature_type, $feature_class );

	}

	static function get_feature_type( $feature_type ) {

		return self::$_feature_type_registry->get_entry( $feature_type );

	}

	/*********************************************/
	/***  Field Storage Type Registry Methods  ***/
	/*********************************************/

	/**
	 *
	 */
	static function initialize_storage_type_registry() {

		self::$_storage_type_registry = new WP_Registry();

	}

	/**
	 * @param string $storage_type_name - Name of storage
	 * @param bool|string $storage_type_class - Classname
	 */
	static function register_storage_type( $storage_type_name, $storage_type_class = false ) {

		self::$_storage_type_registry->register_entry( $storage_type_name, $storage_type_class );

	}

  /**
   * @param string $storage_type
   *
   * @return string
   */
  static function get_storage_type_class( $storage_type ) {

		return self::$_storage_type_registry->get_entry( $storage_type );

	}

	/**
	 * Does the named field view exist?
	 *
	 * @param string $storage_type_name The name of the view that is unique for this class.
	 *
	 * @return bool
	 */
	static function storage_type_exists( $storage_type_name ) {

		return self::$_storage_type_registry->entry_exists( $storage_type_name );

	}

	/*********************************************/
	/*** Prefix related methods                ***/
	/*********************************************/
	/**
	 * Collect $args from list of keys and values into a tree of keys and values.
	 *
	 * Look for $args based on their prefixes (i.e. 'html:').
	 * If found capture the non-prefixed key and value into $collected_args for return.
	 * (Stripping the prefix allows for nested values, i.e. 'label:html:class')
   *
   * @note ONLY collect the first level, so $args['label:html:class'] would become $args['label']['html:class'].
   *       Subnames will get split later thanks to calls that drill down recursively.
   *
   * @param array $prefixed_args
   * @param array $args
	 *
	 * @return array
	 */
	static function collect_args( $prefixed_args, $args = array() ) {
    $args = wp_parse_args( $args, array(
      'prefixes' => false,
      'include' => 'all',   // Or 'prefixed'
    ));
    $collected_args = array();
    if ( is_string( $args['prefixes'] ) ) {
      $args['prefixes'] = array( $args['prefixes'] );
    }
		foreach ( $prefixed_args as $arg_name => $arg_value ) {
			if ( 'all' == $args['include'] && false === strpos( $arg_name, ':' ) ) {
        $collected_args[ $arg_name ] = $arg_value;
			} else {
				list( $new_name, $sub_name ) = preg_split( '#:#', $arg_name, 2 );
				if ( 'all' == $args['include'] || in_array( $new_name, $args['prefixes'] ) ) {
          $collected_args[ 'collected_args' ][ $arg_name ] = $arg_value;
          $collected_args[ $new_name ][ $sub_name ] = $arg_value;
          unset( $collected_args[ $arg_name ] );
        }
			}
		}

		return $collected_args;

	}
	/*********************************************/
	/***  Object Factory Type Methods  ***/
	/*********************************************/

	/**
	 *
	 */
	static function initialize_object_factory_registry() {

		self::$_object_factory_registry = new WP_Registry();

	}

  /**
 	 * @param string $factory_type - Name of object
   * @param callable $factory_callable
   * @param array[] $parameters
 	 */
 	static function register_object_factory( $factory_type, $factory_callable, $parameters = array() ) {

    $factory = new WP_Object_Factory( $factory_type, array(

      'callable' => $factory_callable,
      'parameters' => $parameters,

    ));

    self::$_object_factory_registry->register_entry( $factory_type, $factory );

 	}

  /**
   * @param string $factory_type
   *
   * @return WP_Object_Factory
   */
  static function get_object_factory( $factory_type ) {

		return self::$_object_factory_registry->get_entry( $factory_type );

	}

  /**
   * @param string $class_name
   * @param string $method_name
   *
   * @return bool
   */
  static function has_own_method( $class_name, $method_name ) {

    $has_own_method = false;

    if ( method_exists( $class_name, $method_name ) ) {
      $reflector      = new ReflectionMethod( $class_name, $method_name );
      $has_own_method = $class_name == $reflector->getDeclaringClass()->name;
    }

    return $has_own_method;

  }

  /**
   * Allow invoking of instance methods that are overridden by methods in a child class.
   *
   * This allows for methods as filters and actions without requiring them to call parent::method().
   *
   * @param object $instance
   * @param string $class_name
   * @param string $method_name
   * @param array $args
   *
   * @return mixed
   */
  static function invoke_instance_method( $instance, $class_name, $method_name, $args ) {

    $reflected_class  = new ReflectionClass( $class_name );
    $reflected_method = $reflected_class->getMethod( $method_name );

    return $reflected_method->invokeArgs( $instance, $args );

  }


  /**
   * @param string $class_name
   * @param string $property_name
   *
   * @note UNTESTED
   *
   * @return bool
   */
  static function has_own_static_property( $class_name, $property_name ) {

    $has_own_static_property = false;

    if ( property_exists( $class_name, $property_name ) ) {
      $reflected_property      = new ReflectionProperty( $class_name, $property_name );
      $has_own_static_property = $reflected_property->isStatic();
    }

    return $has_own_static_property;

  }

  /**
   * Allow invoking of instance methods that are overridden by methods in a child class.
   *
   * This allows for methods as filters and actions without requiring them to call parent::method().
   *
   * @note UNTESTED
   *
   * @param string $class_name
   * @param object $object
   * @param string $method_name
   *
   * @return mixed
   */
  static function get_static_method_name( $class_name, $object, $method_name ) {

    $reflected_method = new ReflectionProperty( $class_name, $method_name );

    return $reflected_method->getValue( $object );

  }

  /**
   * @param string $class_name
   * @param string $property_name
   *
   * @return bool
   */
  static function non_public_property_exists( $class_name, $property_name ) {

    $reflection = new ReflectionClass( $class_name );

    if ( ! $reflection->hasProperty( $property_name ) ) {
      $exists = false;
    } else {
      $property_name = $reflection->getProperty( $property_name );
      $exists   = $property_name->isProtected() || $property_name->isPrivate();
    }

    return $exists;

  }
  /**
   * Get an array of class name parent
   *
   * Returns an array of all parent class names with most distant ancenstor first down to parent class,
   * or the named class (if inclusive.)
   *
   * @example array( 'WP_Base', 'WP_Field_Base', 'WP_Text_Field' )
   *
   * @param string $class_name
   * @param bool $inclusive
   *
   * @return array
   */
  static function get_class_parents( $class_name, $inclusive = true ) {

    if ( !( $parents = wp_cache_get( $cache_key = "class_lineage[{$class_name}]" ) ) ) {
      $parents = $inclusive ? array( $class_name ) : array();

      if ( $class_name = get_parent_class( $class_name ) ) {
        $parents = array_merge( self::get_class_parents( $class_name, true ), $parents );
      }

      wp_cache_set( $cache_key, $parents );
    }

    return $parents;

  }

  /**
   * Collect an array of class annotations defined as either class constant or static method.
   * Start with the most distant anscestor down to the current class and merge $annotations.
   *
   * @param object $instance
   * @param string $annotation_name
   * @param array $annotations
   *
   * @return array
   */
  static function get_annotations( $instance, $annotation_name, $annotations = array() ) {

    $class_name = get_class( $instance );

    if ( ! isset( self::$_class_annotations[$key = "{$class_name}::{$annotation_name}"] ) ) {

      $parents = self::get_class_parents( $class_name, true );

      foreach ( $parents as $parent ) {

        if ( defined( $const_ref = "{$parent}::{$annotation_name}" ) ) {

          $annotations = array_merge( $annotations, array( self::constant( $annotation_name, $parent ) ) );

        } else if ( self::has_own_method( $parent, $annotation_name ) ) {

          $annotations = array_merge( $annotations, self::invoke_instance_method( $instance, $parent, $annotation_name, $annotations ) );

        }

      }

      /*
       * Remove any annotations values that are null.
       */
      self::$_class_annotations[$key] = array_filter( $annotations, array( __CLASS__, '_strip_null_elements' ) );

    }
    return self::$_class_annotations[$key];

  }

  /**
   * Call a named method starting with the most distant anscestor down to the current class filtering $value.
   *
   * @param object $object
   * @param string $class_name
   * @param string $method_name
   * @param mixed $value
   *
   * @return mixed
   */
  static function apply_class_filters( $object, $class_name, $method_name, $value ) {
    $args = array_slice( func_get_args(), 3 );
    $parents = self::get_class_parents( $class_name, true );

    foreach ( $parents as $parent ) {
      if ( self::has_own_method( $parent, $method_name ) ) {
        $value = self::invoke_instance_method( $object, $parent, $method_name, array( $value, $args ) );
      }
    }

    return $value;

  }

  /**
   * Call a named method starting with the most distant anscestor down to the current class with no return value.
   *
   * @param object $object
   * @param string $class_name
   * @param string $method_name
   */
  static function do_class_action( $object, $class_name, $method_name ) {
    $args = array_slice( func_get_args(), 3 );
    $parents = self::get_class_parents( $class_name, true );

    foreach ( $parents as $parent ) {
      if ( self::has_own_method( $parent, $method_name ) ) {
        self::invoke_instance_method( $object, $parent, $method_name, array( $args ) );
      }
    }

  }

  /**
   * @param mixed $element
   *
   * @return bool
   */
  static function _strip_null_elements( $element ) {

    return ! is_null( $element );

  }


  /**
   * @param string $class_name
   * @param string $const_name
   *
   * @return mixed
   *
   */
  static function constant( $class_name, $const_name ) {

    return defined( $const_ref = "{$class_name}::{$const_name}" ) ? constant( $const_ref ) : null;

  }



}

WP_Metadata::on_load();
