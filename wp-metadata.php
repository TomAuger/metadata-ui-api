<?php



/**
 * Class WP_Metadata
 */
class WP_Metadata {

	/**
	 * @var string[]
	 */
	private static $_source_files = array(
		'/includes/functions.php',
		'/includes/class-var.php',
		'/includes/html-element.php',
		'/includes/object-type.php',
		'/includes/object.php',
		'/includes/data.php',
		'/includes/tags.php',
		'/includes/item.php',
		'/includes/control.php',
		'/includes/section.php',
		'/includes/panel.php',
		'/includes/storage.php',
		'/controls/text.php',
	);

	/**
	 * @var callable[]
	 */
	static $_class_filter_callables = array();

	/**
	 *
	 */
	static function on_load() {

		add_action( 'admin_init', array( __CLASS__, '_admin_init' ) );

		foreach( self::$_source_files as $source_file ) {

			require __DIR__ . $source_file;

		}
	}

	/**
	 *
	 */
	static function _admin_init() {
		if ( WP_Metadata::is_post_edit_screen() ) {
//			add_action( 'edit_form_top', array( __CLASS__, '_edit_post_form' ) );
//			add_action( 'edit_form_after_title', array( __CLASS__, '_edit_post_form' ) );
//			add_action( 'edit_form_after_editor', array( __CLASS__, '_edit_post_form' ) );
//			add_action( 'edit_form_advanced', array( __CLASS__, '_edit_post_form' ) );
//
//			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
//
//			add_action( 'save_post_' . self::get_current_screen()->post_type, array( __CLASS__, '_save_post' ), 10, 3 );
//
//			// Add global styles for metadata api.
//			add_action( 'admin_enqueue_scripts', array( __CLASS__, '_enqueue_admin_styles' ) );
		}
	}

	/**
	 * @return bool
	 *
	 * @todo For core-dev review. Better way?
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
	 * @todo For core-dev review. Better way?
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
	 * Load css required for the metadata api.
	 *
	 */
	static function _enqueue_admin_styles( $hook ) {

		wp_enqueue_style( 'metadata', plugin_dir_url( __FILE__ ) . 'css/metadata.css', array() );

	}

	/**
	 * Add an action scoped to a class.
	 *
	 * @param string|object $target
	 * @param string $action
	 * @param int $priority
	 *
	 * @return mixed
	 */
	static function add_class_action( $target, $action, $priority = 10 ) {

		self::add_class_filter( $target, $action, $priority );

	}

	/**
	 * Add a filter scoped to a class.
	 *
	 * @param string|object $target  Class name or object
	 * @param string $filter
	 * @param int $priority
	 * @param object $_object
	 *
	 * @return mixed
	 */
	static function add_class_filter( $target, $filter, $priority = 10, $_object = null ) {

		if ( is_object( $target ) ) {
			$class_name = get_class( $target );
		} else {
			$class_name = $target;
		}
		if ( is_null( $_object ) ) {
			$_object = $target;
		}

		if ( method_exists( $class_name, $method_name = "_{$filter}" ) ) {

			if ( $parent_class = get_parent_class( $class_name ) ) {
				/*
				 * If the class has a parent, recurse with the parent class
				 */
				self::add_class_filter( $parent_class, $filter, $priority, $_object );
			}

			$filter = "{$class_name}::{$filter}";

			if ( ! isset( self::$_class_filter_callables[ $filter ] ) ) {

				$reflector = new ReflectionMethod( $class_name, $method_name );

				if ( 1 <= $reflector->getNumberOfParameters() ) {

					self::$_class_filter_callables[ $filter ] = array( $reflector, 'invokeArgs' );

				} else {

					self::$_class_filter_callables[ $filter ] = array( $reflector, 'invoke' );

				}

			}

			$callable = self::$_class_filter_callables[ $filter ];
			add_filter( $filter, $callable, $priority, 'invoke' == $callable[ 1 ] ? 1 : 2 );

		}
	}

	/**
	 * Do an action scoped to a class.
	 *
	 * @param string|object $class
	 * @param string $action
	 * #param mixed $arg1
	 * #param mixed $arg2
	 * #param mixed $arg3
	 * #param mixed $arg4
	 * #param mixed $arg5
	 *
	 * @return mixed
	 */
	static function do_class_action( $class, $action ) {

		$args = func_get_args();

		if ( is_object( $class ) ) {
			$class_name = get_class( $class );

		} else if ( is_string( $class ) && class_exists( $class ) ) {
			$class_name = $class;

		} else {
			$class_name = false;

		}

		if ( ! $class_name ) {
			$value = null;

		} else {
			$action = "{$class_name}::{$action}";

			if ( 2 == count( $args ) ) {

				if ( self::_uses_reflection( $action ) ) {
					do_action( $action, $class );
				} else {
					do_action( $action );
				}

			} else {

				if ( ! self::_uses_reflection( $action ) ) {
					$args = array_slice( $args, 1 );
				}
				call_user_func_array( 'do_action', $args );

			}


		}

	}

	/**
	 * Apply a filter scoped to a class.
	 *
	 * @param string|object $class
	 * @param string $filter
	 * #param mixed $arg1
	 * #param mixed $arg2
	 * #param mixed $arg3
	 * #param mixed $arg4
	 * #param mixed $arg5
	 *
	 * @return mixed
	 */
	static function apply_class_filters( $class, $filter ) {

		$args = func_get_args();

		if ( is_object( $class ) ) {
			$class_name = get_class( $class );

		} else if ( is_string( $class ) && class_exists( $class ) ) {
			$class_name = $class;

		} else {
			$class_name = false;

		}

		if ( ! $class_name ) {
			$value = null;

		} else {

			$filter = "{$class_name}::{$filter}";

			if ( 3 == count( $args ) ) {

				if ( self::_uses_reflection( $filter ) ) {
					$value = apply_filters( $filter, $class, array( $args[ 2 ] ) );
				} else {
					$value = apply_filters( $filter, $args[2] );
				}

			} else {

				if ( ! self::_uses_reflection( $filter ) ) {
					$value   = call_user_func_array( 'apply_filters', array( $filter, $class, array_slice( $args, 2 ) ) );
				} else {
					$args = array_slice( $args, 1 );
					$args[ 0 ] = $filter;
					$value   = call_user_func_array( 'apply_filters', $args );
				}

			}

		}

		return $value;

	}

	/**
	 * Sanitizes an identifier
	 *
	 * An identifier is defined as a string that must start with a letter and can contain letters numbers or underscrores.
	 *
	 * Identifiers are converted to lower case but will return null if the identifier is not valid.
	 *
	 * Dashes are allowed if a single '-' is passed at the 2nd parameter.
	 *
	 * @param string $identifier String to sanitize following the rules of an identifier.
	 *
	 * @param string $allow Typically used to allow a dash in the idenitifier; If needed, pass in a literal string '-'.
	 *
	 * @return null|string
	 */
	static function sanitize_identifier( $identifier, $allow = '' ) {

		$identifier = strtolower( $identifier );

		if ( ! preg_match( '#^[a-z_]#', $identifier ) || preg_replace( "#[^a-z0-9_{$allow}]#", '', $identifier ) != $identifier ) {

			$identifier = null;

		}

		return $identifier;

	}

	/**
	 * @param array $args
	 * @param array $expansions
	 * @return array
	 */
	static function expand_args( $args, $expansions ) {

		foreach( $expansions as $short_name => $long_name ) {

			if ( isset( $args[ $short_name ] ) ) {

				if ( ! isset( $args[ $long_name ] ) ) {
					$args[ $long_name ] = $args[ $short_name ];
				}
				unset( $args[ $long_name ] );

			}

		}
		return $args;
	}

	/**
	 * @param string $class_name
	 * @param array $args
	 *
	 * @return mixed|null
	 */
	static function make_new( $class_name, $args = array() ) {

		if ( ! class_exists( $class_name ) ) {
			$new = null;
		} else {
			$new = call_user_func( array( $class_name, 'make_new' ), $args, $class_name );
		}
		return $new;

	}

	/**
	 * @param string $filter
	 *
	 * @return bool
	 */
	private static function _uses_reflection( $filter ) {

		if ( empty( self::$_class_filter_callables[ $filter ] ) ) {

			$uses_reflection = false;

		} else {

			$callable = self::$_class_filter_callables[ $filter ];
			$uses_reflection = isset( $callable[0] ) && $callable[0] instanceof ReflectionMethod;

		}
		return $uses_reflection;
	}


}
WP_Metadata::on_load();


