<?php
/**
 * Metadata Base Classes
 */

/**
 * Class WP_Metadata_Base
 *
 * This is the "base" class for all other classes in the WP_Metadata 'feature-as-plugin" except for the "core" classes
 * in the /core/ directory or classes whose base class ultimately extends this class.
 *
 * The primary benefits of the WP_Metadata_Base class are :
 *
 *  1. Provide support for Java-like annotations that we use to enable must greater control over instantiation.
 *  2. Provide a run-once class initializer
 *  3. Provide a framework for initializing object properties using $args passed in directly or indirectly.
 *
 * Although WP_Metadata_Base's name implies it is only designed for use by the #wp-metadata feature-as-plugin this
 * class was in fact architected in hopes that it can be renamed WP_Base and used throughout WordPress anywhere
 * there would be a significant benefit to using an OOP-based object hiearchy.
 */
abstract class WP_Metadata_Base {

	/**
	 * @var array Array to capture args passed but not expected by the class.
	 *            Useful for allowing site builder to add values needed for
	 *            customized site logic without having to create a new class,
	 *            when applicable.
	 */
	var $custom_args = array();

	/**
	 * @var array Used to capture args just before assignment to properties.
	 *            Useful for debugging.
	 */
	private $_args = array();

	/**
	 * @var bool[] Array of flags with class names as keys indicating that the
	 *             class has been instantiated at least once and that the
	 *             method initialize_class() has been run for this class and
	 *             all it's ancestor classes.
	 */
	private $_initialized = array();

	/**
	 * Instantiate most any object of the WP_Metadata feature-as-plugin.
	 *
	 * This constructor extends PHP in signficant and powerful ways, support annotations to enable
	 * complex class containment hierarchies to be instantiated directly from the $args passed to
	 * the main object. This is really beneficial for creating flexible Fields and Forms.
	 *
	 * @param array $args
	 *
	 */
	function __construct( $args = array() ) {

		if ( ! isset( $this->_initialized[ $this_class = get_class( $this ) ] ) ) {
			/*
			 * Check to see if any instance of this class has ever been instantiated.
			 */
			$this->_initialized[ $this_class ] = true;

			/*
			 * Runs the $this->initialize_class() method once per each ancestor class, if
			 * declared, and then once for the class itself, if declared. This allows each
			 * declared class a chance to initialize itself.
			 *
			 * IMPORTANT: parent::initialize_class() should NEVER be called inside any
			 * initialize_class() method as it will result in running initializations
			 * multiple times that were designed to be run once.
			 */
			$this->do_class_action( 'initialize_class' );
		}

		if ( ! is_array( $args ) ) {
			/*
			 * If a value is passed in for $args and it's not an array, toss it and give me an array().
			 * Is this needed. Meebe, meebe-not. But it's here just in case.
			 */
			$args = array();
		}

		if ( $this->do_process_args( true, $args ) ) {
			/*
			 * Allow a child class to short-circuit the defaults/expand/collect/assign
			 * steps below. To short-circuit simply implement the do_process_args()
			 * method and return false.
			 */

			/*
			 * Add any default values declared in annotations and
			 * merge with $args to include defaults for and missing
			 * properties but not to overwrite $args values that
			 * exist as elements in the array.
			 */
			$args = $this->get_default_args( $args );

			/*
			 * Use the RegEx entries defined in the CLASS_VARs() 'shortcuts' element to
			 * expand arguments from shortcuts to fully qualified args.
			 *
			 * @example When passed to WP_Field_Base:
			 *
			 *    'label' expands to 'view:features[label]:label_text
			 *    'size'  expands to 'view:features[input]:eleement:size
			 *
			 */
			$args = $this->expand_args( $args );

			/*
			 * Scan the args for colons and convert those into subarrays by paring off
			 * the string to the left of the first colon and using it as an array element
			 * that contains an array of the values stripped.
			 *
			 * @example $args['view:view_type'] collects to $args['view']['view_type']
			 *
			 * In the case of an array then it collects but the array and keys.
			 *
			 * @example $args['features[input]:element:size'] collects to $args['features']['input']['element:size']
			 *
			 * Note that the architecture does not attempt to collect recursively which
			 * is why 'element:size' was not collected.It assumes that a contained class
			 * will handle that collection later.
			 */
			$args = $this->collect_args( $args );

			/*
			 * Capture these args before assignment for debugging, if needed.
			 */
			$this->_args = $args;

			/*
			 * Assign these $args to the properties of this object.
			 *
			 * In the case of properties that expect contained objects as per their annotations
			 * as returned by the PROPERTIES() method, use the ::make_new() factory method
			 * to instantiate and use the 'properties' element of the array returned by
			 * CLASS_VALUES() to build the parameter list for ::make_new().
			 */
			$this->assign_args( $args );

		}

		/*
		 * Call $this->pre_initialize() to filter $args after assignment but just prior
		 * to runing $this->initialize(), if needed.  There are some cases where this
		 * is imporant.
		 *
		 * @see WP_Field_Input_Feature->pre_initialize() for an example.
		 *
		 * Runs $this->pre_initialize() method only once per each ancestor class, if
		 * declared, and then once for the class itself, if declared. This allows
		 * each declared class a chance to initialize itself.
		 *
		 * IMPORTANT: parent::pre_initialize() should NEVER be called inside any
		 * initialize_class() method as it will result in running initializations
		 * multiple times that were designed to be run once.
		 */
		$args = $this->apply_class_filters( 'pre_initialize', $args );

		/*
		 * Call $this->initialize() to initialize any property values that cannot
		 * be initialized via generic methods, such as setting the @for attribute
		 * of the <label> feature to equal the @id attribute of the <input>
		 * feature.
		 *
		 * @see WP_Field_View_Base->initialize() for an example.
		 *
		 * Runs $this->initialize() method only once per each ancestor class, if
		 * declared, and then once for the class itself, if declared. This allows
		 * each declared class a chance to initialize itself.
		 *
		 * IMPORTANT: parent::initialize() should NEVER be called inside any
		 * initialize() method as it will result in running initializations
		 * multiple times that were designed to be run once.
		 */
		$this->do_class_action( 'initialize', $args );


		if ( ! WP_DEBUG ) {
			/**
			 * If we are not running with WP_DEBUG == true then clear out the
			 * memory used by $_args since we primarily only captured for
			 * debugging purposes anyway.
			 */
			$this->_args = null;

		}

	}

	/**
	 * CLASS_VALUES() is designed to be subclassed for class-level annotations.
	 *
	 * This ia a special purpose function (hence the UPPER_CASE() naming
	 * format) which is designed for providing annotation values at a class
	 * level.
	 *
	 * @important Return values SHOULD BE CACHABLE ACROSS PAGE LOADS from child
	 *            class implementations.
	 *
	 * @important Child class implementations SHOULD NOT call parent::CLASS_VALUES().
	 *
	 * The current recognized keys are the following (although plugins are free
	 * to add their own while following the best practice of prefixing[1]) and
	 * there is no set structure for the values:
	 *
	 *  - 'defaults'    - An associative array of defaults for $args. Useful for
	 *                    setting default $args for contained objects that will
	 *                    be instantiated automatically inside $this->assign_args().
	 *
	 *  - 'shortnames' -  An associative array with Regex as keys and Match Patterns
	 *                    as values used to transform short $arg names into fully
	 *                    qualified $arg names.
	 *
	 *  - 'parameters' -  A simple array whose values defines the parameter(s)
	 *                    needed for the make_new() method.
	 *
	 * @return array The full associative array of CLASS_VALUES for this class.
	 *
	 * @see [1] http://nacin.com/2010/05/11/in-wordpress-prefix-everything/
	 */
	static function CLASS_VALUES() {

		return array(
				/*
				 * Although the base class does not define its own make_new() method,
				 * the base pattern for make_new() is to expect one parameter being
				 * an associative array of "$args."
				 *
				 * So set this here for simple classes so those classes do not have to.
				 */
				'parameters' => array( '$args' ),
		);

	}

	/**
	 * PROPERTIES() is designed to be subclassed for class-level annotations.
	 *
	 * This ia a special purpose function (hence the UPPER_CASE() naming
	 * format) which is designed for providing annotations at a class property
	 * level.
	 *
	 * @important Return values SHOULD BE CACHABLE ACROSS PAGE LOADS from child
	 *            class implementations.
	 *
	 * @important Child class implementations SHOULD NOT call parent::CLASS_VALUES().
	 *
	 * The current recognized keys match the property names for the proerties of
	 * the class they annotate. Their values should be an associative array
	 * that are valid $arg values for instantiating a WP_Annotated_Property
	 * however 'type' is used as a shorthand for $property_type.
	 *
	 * However a sitebuilder, or a plugin or theme developer can add add their kyes
	 * assumubg they follow the best practice of prefixing[1]):
	 *
	 * Commonly used keys:
	 *
	 *  - 'type'        - Often a class name but may alternately designate an array of
	 *                    same class name where the class name is a base class that
	 *                    has a make_new() factory static method. The syntax for
	 *                    an array of classes is 'Class_Name[]' where the empty
	 *                    square brackets denote the array. The type may also be
	 *                    scalar (i.e. 'int') or an array of scalar (i.e. 'string[]').
	 *                    This gets stored in the $property_type property of a
	 *                    WP_Annotated_Property object.
	 *
	 *  - 'default'     - The default value for this property if a value is not
	 *                    provided in the $args array passed to __construct().
	 *
	 *  - 'auto_create' - Defaults to true, setting to false tells this class not
	 *                    to automatically create instances of the contained object
	 *                    for this property. The most common use for this would be
	 *                    in the case of a 'parent or 'owner' object assigned to a
	 *                    property of a child or contained object.
	 *
	 *  - 'registry'    - The WP_Registry Type which should also have either been
	 *                    hardcoded in WP_Metadata::$_registries or registered using
	 *                    WP_Metadata::register_registry(). This registry is used to
	 *                    look up class names by key when 'type' is an array of objects,
	 *                    i.e. 'WP_Feature_Base[]'.
	 *
	 *  - 'keys'        - The array of array key names (string) for when 'type' is
	 *                    an array of objects, i.e. for when 'type' is the array
	 *                    'WP_Feature_Base[]' then the array of key names
	 *                    is: array('label','input','help','message','infobox')
	 *
	 *  - 'prefix'      - The qualifing prefix for this property. This can be specified
	 *                    if you need to change the name of the $args prefix to be
	 *                    different than the $property_name. For example, the prefix
	 *                    for the view of a field is 'view' this this is a valid $arg
	 *                    key for instantiating a field: 'view:view_type' although
	 *                    for that specific case 'view_type' can be used as a shorthand.
	 *
	 * @return array The full associative array of PROPERTIES for this class.
	 *
	 * @see [1] http://nacin.com/2010/05/11/in-wordpress-prefix-everything/
	 */
	static function PROPERTIES() {

		/*
		 * No properties are declared in this base class.
		 */
		return array();

	}

	/**
	 * Do an action specific to this class and its ancestor classes.
	 *
	 * This function starts by calling the specified field on the most distant ancestor class which defines the filter
	 * and then calls its next child that defines the filter, and so on.
	 *
	 * @example Calling $this->do_action( 'do_something' ) on an instance of 'WP_Text_Field' which extends
	 *          'WP_Field_Base' which extends 'WP_Metadata_Base' would be the same as calling the following code
	 *          assuming that each class defined a do_something() method and that PHP could cast objects to their
	 *          subclasses and in this order:
	 *
	 *          <code>
	 *          $field = (WP_Metadata_Base)$field;
	 *          $field->do_something();
	 *
	 *          $field = (WP_Field_Base)$field;
	 *          $field->do_something();
	 *
	 *          $field = (WP_Text_Field)$field;
	 *          $field->do_something();
	 *          </code>
	 *
	 *
	 * @important Implementations SHOULD NOT call parent::{$$action_method}() as
	 *            WP_Metadata::do_class_action() will do that.
	 *
	 * @important This is NOT a replacement for do_action(); instead it is used for different use-cases.
	 *
	 * This function is used instead of do_action() for when the scope and visibility should rightly stay
	 * within the context of the class, and should NOT REQUIRE the implementor of the child class to
	 * ensure that context is maintained.
	 *
	 * Using this function instead of do_action() *where it applies* should result in more robust code.
	 *
	 * There should still be do_action()s placed in code for when hooking is important in non-subclassing
	 * use-cases.
	 *
	 * @uses WP_Metadata::do_class_action() Called after this function packages its parameters.
	 *
	 * @param string $action_method The name of the method to call on the object cast as instances of ancestor classes
	 *                              and then on itself, assuming the method is declared for a given class.
	 *
	 */
	function do_class_action( $action_method ) {

		/*
		 * Insert a reference to $this and this object's class name ahead of
		 * the parameters passed to this function.
		 */
		$args = array_merge( array( $this, get_class( $this ) ), func_get_args() );

		/*
		 * Call WP_Metadata::do_class_action() with the $args $this, class name and then
		 * whatever $args where passed to this function.
		 */
		call_user_func_array( array( 'WP_Metadata', 'do_class_action' ), $args );

	}

	/**
	 * Do an action specific to this class and its ancestor classes.
	 *
	 * This function starts by calling the specified field on the most distant ancestor class which defines the filter
	 * and then calls its next child that defines the filter, and so on.
	 *
	 * This function has a responsibility to return it's first parameter 'filtered' in whatever way that 'filtered'
	 * means for this function (which could mean no change if the function simply wants to be called to do something
	 * else at that point, although that usage would generally be frowned on unless there is no other way to acheive
	 * the end goal.)
	 *
	 * @example Calling $this->apply_class_filters( 'filter_args' ) on an instance of 'WP_Text_Field' which extends
	 *          'WP_Field_Base' which extends 'WP_Metadata_Base' would be the same as calling the following code
	 *          assuming that each class defined a filter_args() method and that PHP could cast objects to their
	 *          subclasses and in this order:
	 *
	 *          <code>
	 *          $field = (WP_Metadata_Base)$field;
	 *          $args = $field->filter_args( $args );
	 *
	 *          $field = (WP_Field_Base)$field;
	 *          $args = $field->filter_args( $args );
	 *
	 *          $field = (WP_Text_Field)$field;
	 *          $args = $field->filter_args( $args );
	 *          </code>
	 *
	 *
	 * @important Implementations SHOULD NOT call parent::{$filter_method}() as
	 *            WP_Metadata::apply_class_filters() will do that.
	 *
	 * @important This is NOT a replacement for apply_filters(); instead it is used for different use-cases.
	 *
	 * This function is used instead of apply_filters() for when the scope and visibility should rightly
	 * stay within the context of the class, and should NOT REQUIRE the implementor of the child class to
	 * ensure that context is maintained.
	 *
	 * Using this function instead of apply_filters() *where it applies* should result in more robust code.
	 *
	 * There should still be apply_filters()s placed in code for when hooking is important in non-subclassing
	 * use-cases.
	 *
	 * @uses WP_Metadata::apply_class_filters() Called after this function packages its parameters.
	 *
	 * @param string $filter_method The name of the method to call on the object cast as instances of ancestor classes
	 *                              and then on itself, assuming the method is declared for a given class.
	 * @param mixed $value The value to filter.
	 *
	 * @return mixed
	 */
	function apply_class_filters( $filter_method, $value ) {

		if ( is_null( $args = func_get_args() ) ) {

			$args = array( $this );

		} else {

			array_unshift( $args, $this );

		}

		return call_user_func_array( array( 'WP_Metadata', 'apply_class_filters' ), $args );

	}

	/**
	 * Return true if _construct() should call defaults/expand/collect/assign for the $args passed.
	 *
	 * do_process_args() is designed to be subclassed for class-level annotations to allow subclasses
	 * to override and/or replace the defaults/expand/collect/assign processing, if needed.
	 *
	 * @param bool $continue The value of continue passed in by either this class or the child class.
	 *
	 * @param array $args The $args passed to __construct()
	 *
	 * @return bool If false returned then the defaults/expand/collect/assign processing of $args is bypassed.
	 *
	 * @important Assuming $continue is passed in as false then this subclassed function's responsibility
	 *            is to return false as well unless it knowingly is able to override the reason that false
	 *            is passed in (which is unlikely given it's defined in a parent class.)
	 */
	function do_process_args( $continue, $args = array() ) {

		/*
		 * Return what was passed since sole reason for being here is to allow subclasses to override this function.
		 */
		return $continue;

	}

	/**
	 * Get the default $arg names and values declared for the class of $this instance and merge in those passed.
	 *
	 * The $args passed in take precedent over default $args.
	 *
	 * This function does the work to collect up the default args the first time it is called after which it
	 * retrieves the value from cache.
	 *
	 *
	 * @param $args array
	 *
	 * @return array
	 */
	function get_default_args( $args = array() ) {

		/*
		 * Check the object cache for "{$class_name}::default_args"
		 */
		if ( !( $default_args = wp_cache_get( $cache_key = get_class( $this ) . '::default_args', 'wp-metadata' ) ) ) {
			/*
			 * If this is the first call of this method for the this class and thus the cache has yet to be set...
			 */

			/*
			 * First get the annotations that are available
			 */
			$annotations = $this->get_property_annotations();

			/*
			 * Create a variable to hold the default $args and then initialize it
			 * with any class default $argsdefined in the 'defaults' argument of
			 * the CLASS_ARGS() method.
			 */
			$default_args = self::get_class_defaults();

			foreach ( $annotations as $annotation_name => $annotation ) {
				/*
				 * For each of the available annotations
				 */

				if ( ! is_null( $annotation->default )  ) {
					/*
					 * Capture the property's default if a default has been set.
					 * Overwrite any defaults that were defined
					 */
					$default_args[ $annotation_name ] = $annotation->default;

				}

			}

			/*
			 * Now store all that work in the object cache!
			 */
			wp_cache_set( $cache_key, $default_args, 'wp-metadata' );

		}

		/*
		 * Finally, merge the $args passed in over top of the default $args, if there were any.
		 */
		return count( $args ) ? array_merge( $default_args, $args ) : $default_args;
	}

	/**
	 *
	 * @return WP_Annotated_Property[]
	 */
	function get_property_annotations() {

		$class_name = get_class( $this );

		$cache_key = "{$class_name}::property_annotations";

		if ( !( $property_annotations = wp_cache_get( $cache_key, 'wp-metadata' ) ) ) {

			$property_annotations = WP_Metadata::get_class_vars( $class_name, 'PROPERTIES' );

			/**
			 * Finally, convert all annotated properties to a WP_Annotated_Property class.
			 */
			foreach ( $property_annotations as $property_name => $property_args ) {

				$property_annotations[ $property_name ] = new WP_Annotated_Property( $property_name, $property_args );

			}

			wp_cache_set( $cache_key, $property_annotations, 'wp-metadata' );

		}

		return $property_annotations;

	}

	/**
	 * @return array
	 */
	function get_class_defaults() {

		$defaults = WP_Metadata::get_class_value( get_class( $this ), 'defaults' );

		return is_array( $defaults ) ? $defaults : array();

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function expand_args( $args ) {

		if ( count( $shortnames = $this->get_shortnames( $args ) ) ) {

			foreach ( $shortnames as $regex => $result ) {
				foreach ( $args as $name => $value ) {
					if ( preg_match( "#{$regex}#", $name, $matches ) ) {

						$args['_expanded_args'][ $name ] = $value;

						unset( $args[ $name ] );

						$new_name = $result;
						if ( 1 <= ( $top_index = count( $matches ) - 1 ) ) {
							for ( $i = 1; $i <= $top_index; $i ++ ) {
								$new_name = str_replace( '$' . $i, $matches[ $i ], $new_name );
							}
						}
						$args[ $new_name ] = $value;
					}
				}
			}

		}

		return $args;

	}

	/**
	 * Returns an array of shortname regexes as array key and expansion as key value.
	 *
	 * Subclasses should define 'shortnames' element in CLASS_VALUES() function array return value:
	 *
	 *    return array(
	 *      $regex1 => $shortname1,
	 *      $regex2 => $shortname2,
	 *      ...,
	 *      $regexN => $shortnameN,
	 *    );
	 *
	 * @example:
	 *
	 *  return array(
	 *    'shortnames'  =>  array(
	 *      '^label$'                     => 'view:label:label_text',
	 *      '^label:([^_]+)$'             => 'view:label:$1',
	 *      '^(input|element):([^_]+)$'   => 'view:input:element:$2',
	 *      '^(input:)?wrapper:([^_]+)$'  => 'view:input:wrapper:$2',
	 *      '^view_type$'                 => 'view:view_type',
	 *     ),
	 *  );
	 *
	 * @note   Multiple shortnames can be applied so order is important.
	 *
	 * @return array
	 */
	function get_shortnames( $args = array() ) {

		$class_vars = $this->get_class_vars( 'CLASS_VALUES' );

		$shortnames = ! empty( $class_vars['shortnames'] ) && is_array( $class_vars['shortnames'] )
				? $class_vars['shortnames']
				: array();

		return $shortnames;

	}

	/**
	 * @param string $class_var
	 *
	 * @return array
	 */
	function get_class_vars( $class_var ) {

		return WP_Metadata::get_class_vars( get_class( $this ), $class_var );

	}

	/**
	 * collect $args from delegate properties. Also store in $this->delegated_args array.
	 *
	 * @example
	 *
	 *  $input = array(
	 *    'field_name' => 'Foo',
	 *    'html:size' => 50,     // Will be split and "collect" like
	 *    'wrapper:size' => 25,
	 *  );
	 *  print_r( self::collect_args( $input ) );
	 *  // Outputs:
	 *  array(
	 *    'field_name' => 'Foo',
	 *    'html' => array( 'size' => 50 ),
	 *    'wrapper' => array( 'html:size' => 25 ),
	 *  );
	 *
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	function collect_args( $args ) {

		$args = WP_Metadata::collect_args( $args, $this->get_property_prefixes() );

		return $args;

	}

	/**
	 * @return string[]
	 */
	function get_property_prefixes() {

		$cache_key = ( $class_name = get_class( $this ) ) . '::property_prefixes';

		if ( !( $property_prefixes = wp_cache_get( $cache_key, 'wp-metadata' ) ) ) {

			$property_prefixes = array();

			$annotated_properties = $this->get_property_annotations( $class_name );

			foreach ( $annotated_properties as $field_name => $annotated_property ) {

				if ( $annotated_property->is_class() || $annotated_property->is_array() && ! empty( $annotated_property->prefix ) ) {

					$property_prefixes[ $field_name ] = $annotated_property->prefix;

				}

			}

			wp_cache_set( $cache_key, $property_prefixes, 'wp-metadata' );

		}

		return $property_prefixes;

	}

	/**
	 * Assign the element values in the $args array to the properties of this object.
	 *
	 * @param array $args An array of name/value pairs that can be used to initialize an object's properties.
	 */
	function assign_args( $args ) {

		$class_name = get_class( $this );

		$args = array_merge( $this->get_defaulted_property_values(), $args );

		$args = $this->_sort_args_scaler_types_first( $args );

		/*
		 * Assign the arg values to properties, if they exist.
		 * If no property exists capture value in the $this->custom[] array.
		 */
		foreach ( $args as $name => $value ) {

			$property = $property_name = false;

			/**
			 * @var WP_Annotated_Property $property
			 */
			if ( '$' == $name[0] || preg_match( '#^_(expanded|collected)_args$#', $name ) ) {

				continue;

			} else if ( method_exists( $this, $method_name = "set_{$name}" ) ) {

				call_user_func( array( $this, $method_name ), $value );

			} else if ( $this->has_property_annotations( $name ) ) {

				$annotated_property = $this->get_annotated_property( $property_name = $name );

				if ( $annotated_property->auto_create ) {

					if ( $annotated_property->is_class() ) {

						$object_args = $this->extract_prefixed_args( $annotated_property->prefix, $args );

						$object_args['$value']    = $value;
						$object_args['$parent']   = $this;
						$object_args['$property'] = $annotated_property;

						$value = $annotated_property->make_object( $object_args );

					} else if ( $annotated_property->is_array() ) {

						if ( ! empty( $value ) ) {

							$parent_class_name = $annotated_property->array_of;

							if ( is_array( $annotated_property->keys )
							     && ! empty( $annotated_property->registry )
							     && WP_Metadata::registry_exists( $annotated_property->registry )
							) {

								foreach ( $annotated_property->keys as $key_name ) {

									$object_args = isset( $value[ $key_name ] ) ? $value[ $key_name ] : array();

									$object_args['$value']    = $key_name;
									$object_args['$parent']   = $this;
									$object_args['$property'] = $annotated_property;

									$class_name = WP_Metadata::get_registry_item( $annotated_property->registry, $key_name );

									if ( ! is_subclass_of( $class_name, $parent_class_name ) ) {

										$error_msg = __( 'ERROR: No registered class %s in registry %s.', 'wp-metadata' );
										trigger_error( sprintf( $error_msg, $key_name, $annotated_property->registry ) );

									} else {

										$parameters = WP_Metadata::build_property_parameters( $class_name, $object_args );

										$value[ $key_name ] = call_user_func_array( array( $class_name, 'make_new' ), $parameters );

									}

								}

							}

						}

					}

				}

			} else if ( property_exists( $this, $name ) ) {

				$property_name = $name;

			} else if ( property_exists( $this, $non_public_name = "_{$name}" ) ) {

				$property_name = $non_public_name;

			} else {

				$this->custom_args[ $name ] = $value;

			}

			if ( $property_name ) {

				$this->{$property_name} = $value;

			}

		}

	}

	/**
	 * Return an array of annotated property names and their default values for the current class.
	 *
	 * @return array
	 */
	function get_defaulted_property_values() {

		$cache_key = get_class( $this ) . '::defaulted_property_values';

		if ( !( $property_values = wp_cache_get( $cache_key, 'wp-metadata' ) ) ) {

			$property_values = array();

			foreach ( $this->get_property_annotations() as $class_name => $property ) {

				if ( ! $property->auto_create ) {
					continue;
				}

				$property_name = $property->property_name;

				if ( is_null( $property->default ) && isset( $this->{$property_name} ) ) {

					$default_value = $this->{$property_name};

				} else {

					if ( 'array' == $property->property_type && isset( $property->keys ) ) {

						$default_value = array_fill_keys( $property->keys, $property->default );

					} else {

						$default_value = $property->default;

					}

				}

				$property_values[ $property_name ] = $default_value;

			}

			wp_cache_set( $cache_key, $property_values, 'wp-metadata' );

		}

		return $property_values;
	}

	/**
	 * @param string $property_name
	 *
	 * @return bool
	 */
	function has_property_annotations( $property_name ) {

		$properties = $this->get_property_annotations();

		return isset( $properties[ $property_name ] );

	}

	/**
	 * Gets array of properties field names that should not get a prefix.
	 *
	 * @param string $property_name
	 *
	 * @return WP_Annotated_Property|bool
	 */
	function get_annotated_property( $property_name ) {

		$annotated_properties = $this->get_property_annotations();

		return isset( $annotated_properties[ $property_name ] ) ? $annotated_properties[ $property_name ] : null;

	}

	/**
	 * @param string $prefix
	 * @param array $args
	 *
	 * @return mixed|array;
	 */
	function extract_prefixed_args( $prefix, $args ) {

		if ( ! $prefix || empty( $args[ $prefix ] ) || ! is_array( $prefixed_args = $args[ $prefix ] ) ) {

			$prefixed_args = array();

		}

		return $prefixed_args;

	}

	/**
	 *
	 */
	function initialize_class() {
		/**
		 * Initialize Class Property Annotations for the class of '$this.'
		 */
		$this->get_property_annotations();

	}

	/**
	 * @param string $const_name
	 * @param bool|string $class_name
	 *
	 * @return mixed
	 */
	function constant( $const_name, $class_name = false ) {

		if ( ! $class_name ) {

			$class_name = get_class( $this );

		}

		return WP_Metadata::constant( $class_name, $const_name );

	}

	/**
	 * @return array
	 */
	function get_properties() {

		return array_merge( $this->get_property_annotations(), get_object_vars( $this ) );

	}

	/**
	 * @param string $annotation_name
	 * @param string $property_name
	 *
	 * @return mixed
	 */
	function get_annotation_value( $annotation_name, $property_name ) {

		if ( $property = $this->get_annotated_property( $property_name ) ) {

			$value = $property->get_annotation_value( $annotation_name );

		} else {

			$value = null;

		}

		return $value;
	}

	/**
	 * @param string $annotation_name
	 * @param string $property_name
	 *
	 * @return mixed
	 */
	function get_annotation_custom( $annotation_name, $property_name ) {

		if ( $property = $this->get_annotated_property( $property_name ) ) {

			$value = $property->get_annotation_custom( $annotation_name );

		} else {

			$value = null;

		}

		return $value;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	private function _sort_args_scaler_types_first( $args ) {

		uksort( $args, array( $this, '_scaler_types_first' ) );

		return $args;

	}

	/**
	 * @param string $field1
	 * @param string $field2
	 *
	 * @return int
	 */
	private function _scaler_types_first( $field1, $field2 ) {

		$sort       = 0;
		$has_field1 = $this->has_property_annotations( $field1 );
		$has_field2 = $this->has_property_annotations( $field2 );

		if ( $has_field1 && $has_field2 ) {

			$field1 = $this->get_annotated_property( $field1 );
			$field2 = $this->get_annotated_property( $field2 );

			if ( $field1->is_array() && $field2->is_class() ) {
				$sort = - 1;

			} else if ( $field1->is_class() && $field2->is_array() ) {
				$sort = + 1;

			}

		} else if ( $has_field1 ) {
			$sort = + 1;

		} else if ( $has_field2 ) {
			$sort = - 1;

		}

		return $sort;

	}

	/**
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return mixed
	 */
	function __call( $method_name, $args = array() ) {

		$result = false;

		/**
		 * @todo Move to the WP_View_Base class.
		 */
		if ( preg_match( '#^the_(.*)$#', $method_name, $match ) ) {

			$method_exists = false;

			if ( method_exists( $this, $method_name = $match[1] ) ) {

				$method_exists = true;

			} elseif ( method_exists( $this, $method_name = "{$method_name}_html" ) ) {

				$method_exists = true;

			} elseif ( method_exists( $this, $method_name = "get_{$method_name}" ) ) {

				$method_exists = true;

			} elseif ( method_exists( $this, $method_name = "get_{$match[1]}" ) ) {

				$method_exists = true;

			}

			if ( $method_exists ) {

				echo call_user_func_array( array( $this, $method_name ), $args );

				$result = true;

			}
		}

		return $result;

	}
}

/**
 * Class WP_Field_Base
 *
 * @mixin WP_Field_View_Base
 */
abstract class WP_Field_Base extends WP_Metadata_Base {

	/**
	 *
	 */
	const FIELD_TYPE = 'unspecified';

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
	 * @return null|WP_Feature_Base
	 */
	function make_feature( $feature_type, $feature_args ) {

		return WP_Metadata::make_feature( $this, $feature_type, $feature_args );

	}

	/**
	 * @return WP_Feature_Base
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

/**
 * Class WP_View_Base
 *
 * @TODO Refactor so this class can handle an arbitrary list of properties, not just 'wrapper' and 'element'
 *
 */
abstract class WP_View_Base extends WP_Metadata_Base {

	/**
	 * @var array
	 */
	private static $_shortnames = array();
	/**
	 * @var WP_Html_Element
	 */
	var $wrapper = null;
	/**
	 * @var WP_Html_Element
	 */
	var $element = null;
	/**
	 * @var WP_Metadata_Base
	 */
	var $owner;

	/**
	 * @return array
	 */
	static function PROPERTIES() {

		return array(
				'wrapper' => array( 'type' => 'WP_Html_Element' ),
				'element' => array( 'type' => 'WP_Html_Element' ),
		);

	}

	/**
	 * @return string
	 */
	function get_html() {

		if ( is_object( $wrapper = $this->wrapper ) && method_exists( $wrapper, 'get_html' ) ) {

			$wrapper->value = $this->get_element_html();

			$html = $wrapper->get_html();

		} else {

			$html = null;

		}

		return $html;

	}

	/**
	 * @return string
	 */
	function get_element_html() {

		$this->element->value = $this->get_element_value();

		return $this->element->get_html();

	}

	/**
	 * Get the value of the element.
	 *
	 * @note: Typically this will be overridden in child classes.
	 *
	 * @return bool
	 */
	function get_element_value() {
		return false;
	}

	/**
	 * Return the HTML tag to be used by this class.
	 *
	 * @return array
	 */
	function get_wrapper_tag() {

		if ( ! ( $html_tag = $this->get_annotation_custom( 'html_tag', 'wrapper' ) ) ) {

			$html_tag = 'div';

		}

		return $html_tag;

	}

	/**
	 * @param array $feature_args
	 *
	 * @return array
	 */
	function initialize( $feature_args ) {

		$this->element->append_class( $this->initialize_attribute( 'class', 'element' ) );
		$this->element->set_id( $this->initialize_attribute( 'id', 'element' ) );

		$this->wrapper->append_class( $this->initialize_attribute( 'class', 'wrapper' ) );
		$this->wrapper->set_id( $this->initialize_attribute( 'id', 'wrapper' ) );

		return '';
	}


	/**
	 * @param string $attribute_name
	 * @param string $html_element_name
	 *
	 * @return mixed
	 */
	function initialize_attribute( $attribute_name, $html_element_name ) {
		$value = null;
		switch ( $element_attribute = "{$html_element_name}_{$attribute_name}" ) {

			case 'element_name':
			case 'element_id':
			case 'element_class':

				if ( method_exists( $this, $method_name = "initial_{$element_attribute}" ) ) {

					$value = $this->{$method_name}();

				} else {

					switch ( $element_attribute ) {
						case 'element_name':
							$value = 'element_name_not_set_in_child_class';
							break;

						case 'element_id':
							$value = str_replace( '_', '-', $this->element->get_name() );
							break;

						case 'element_class':
							$value = '';
							break;
					}

				}
				break;

			case 'wrapper_id':

				$value = $this->element->get_id() . '-wrapper';
				break;

			case 'wrapper_class':

				if ( $classes = $this->element->get_class() ) {

					$classes = explode( ' ', $classes );

					foreach ( $classes as &$class ) {

						$class = trim( $class ) . '-wrapper';

					}

					$value = implode( ' ', $classes );

				}

				break;

		}

		return $value;
	}

}

/**
 * Class WP_Field_View_Base
 *
 * @mixin WP_Field_Base
 * @property WP_Field_Input_Feature $input
 * @property WP_Field_Label_Feature $label
 * @property WP_Field_Help_Feature $help
 * @property WP_Field_Message_Feature $message
 * @property WP_Field_Infobox_Feature $infobox
 *
 */
abstract class WP_Field_View_Base extends WP_View_Base {

	/**
	 * @var array[]
	 */
	private static $_shortnames = array();
	/**
	 * @var string
	 */
	var $view_type;
	/**
	 * @var WP_Field_Base
	 */
	var $field;
	/**
	 * @var bool|array
	 */
	var $features = false;

	/**
	 * @param string $view_type
	 * @param WP_Field_Base|null $field
	 * @param array $view_args
	 */
	function __construct( $view_type, $field, $view_args = array() ) {

		$this->view_type = $view_type;

		if ( is_object( $field ) ) {

			$field->view = $this;

		}

		$this->field = $field;

		parent::__construct( $view_args );

		$this->owner = $field;

	}

	/**
	 * @return array
	 */
	static function CLASS_VALUES() {
		return array(
				'parameters' => array(
						'view_type',
						'$parent',
						'$value',
				)
		);
	}

	/**
	 * @return array
	 */
	static function PROPERTIES() {

		return array(
				'field'    => array( 'type' => 'WP_Field_Base', 'auto_create' => false ),
				'wrapper'  => array( 'type' => 'WP_Html_Element' ),
				'features' => array(
						'type'     => 'WP_Feature_Base[]',
						'default'  => '$key_name',
						'registry' => 'feature_types',    // @todo Is $registry needed?
						'keys'     => array(
								'label',
								'input',
								'help',
								'message',
								'infobox',
						),
				)
		);

	}

	/**
	 * @param string $view_type
	 * @param WP_Field_Base|null $field
	 * @param array $view_args
	 *
	 * @return WP_Field_View_Base
	 *
	 */
	static function make_new( $view_type, $field, $view_args = array() ) {

		$view = false;

		if ( ! isset( $view_args['view_type'] ) ) {
			/*
			 * We have to do this normalization of the 'type' $arg prior to
			 * the Field classes __construct() because it drives the class used
			 * to instantiate the View. All other $args can be normalized
			 * in the Field class constructor.
			 */
			if ( ! isset( $view_args['type'] ) ) {

				$view_args['view_type'] = 'text';

			} else {

				$view_args['view_type'] = $view_args['type'];

				unset( $view_args['type'] );

			}

		}

		$view_type_args = WP_Metadata::get_field_view_type_args( $view_args['view_type'] );

		if ( is_string( $view_type_args ) && class_exists( $view_type_args ) ) {

			/**
			 * View Type is a Class name
			 */
			$view = new $view_type_args( $view_type, $field, $view_args );

		} else if ( is_array( $view_type_args ) ) {

			/**
			 * View Type passed to make_new() is a 'Prototype'
			 */
			$view_args = wp_parse_args( $view_args, $view_type_args );

			$view = self::make_new( $view_name, $object_type, $view_args );

		}

		if ( $view ) {

			if ( property_exists( $field, 'field' ) ) {

				$view->field = $field;

			}

		} else {

			$view = null;

		}

		return $view;

	}

	/**
	 * @param string $view_class
	 *
	 * @return string[]
	 */
	static function get_input_tag( $view_class ) {

		return WP_Metadata::get_view_input_tag( $view_class );

	}

	/**
	 * @param $args
	 */
	function initialize( $args ) {
		/**
		 * @var WP_Field_Label_Feature $label
		 */
		if ( ! empty( $this->features['label'] ) && is_object( $label = $this->features['label'] ) ) {
			$label->element->set_attribute_value( 'for', $this->features['input']->element->get_id() );
		}

	}

	function initial_element_id() {

		return $this->field->field_name . '-metadata-field';

	}

	/**
	 * @return bool|string
	 */
	function initial_element_class() {

		return "metadata-field";

	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function get_shortnames( $args = array() ) {

		if ( ! isset( self::$_shortnames[ $class_name = get_class( $this ) ] ) ) {

			$shortnames = parent::get_shortnames();

			$properties = $this->get_class_vars( 'PROPERTIES' );

			if ( ! empty( $properties['features']['keys'] ) && is_array( $feature_keys = $properties['features']['keys'] ) ) {

				$features                           = implode( '|', $feature_keys );
				$shortnames["^({$features}):(.+)$"] = 'features[$1]:$2';

			}

			if ( $attributes = $this->get_view_input_attributes() ) {

				$attributes                                                    = implode( '|', $attributes );
				$shortnames["^features\\[([^]]+)\\]:({$attributes})$"]         = 'features[$1]:element:$2';
				$shortnames["^features\\[([^]]+)\\]:wrapper:({$attributes})$"] = 'features[$1]:wrapper:$2';

			}
			self::$_shortnames[ $class_name ] = $shortnames;

		}

		return self::$_shortnames[ $class_name ];

	}

	/**
	 * Delegate to $field explicitly since it is defined in base class.
	 *
	 * @return array
	 */
	function get_prefix() {
		/**
		 * @var WP_Metadata_Base $field
		 */
		$field = $this->field;

		if ( ! $field->has_property_annotations( $field->field_name ) ) {

			$prefix = false;

		} else {

			$prefix = $field->get_annotated_property( $field->field_name )->prefix;

		}

		return $prefix;
	}

	/**
	 * Convenience so users can use a more specific name than get_html().
	 *
	 * @return string
	 */
	function get_field_html() {

		return $this->get_html();

	}

	/**
	 * @return string
	 */
	function get_element_html() {

		return $this->get_features_html();

	}

	/**
	 * @return array
	 */
	function get_features_html() {

		$features_html = array();

		foreach ( $this->get_feature_types() as $feature_type ) {
			/**
			 * @var WP_Feature_Base $feature
			 */
			$feature = $this->features[ $feature_type ];

			if ( 'input' == $feature_type ) {

				$features_html[ $feature_type ] = $this->get_input_html();

			} else {

				$features_html[ $feature_type ] = $feature->get_feature_html();

			}

		}

		return implode( "\n", $features_html );

	}

	/**
	 * Gets array of field feature type names
	 *
	 * @return array
	 */
	function get_feature_types() {

		$features = $this->get_annotated_property( 'features' );

		return is_array( $features->keys ) ? $features->keys : array();

	}

	/**
	 *  Allow Input HTML to be overridden in Field or Field View
	 *
	 *  To override in Field, implement get_input_html().
	 *  To override in Field View, implement get_input_html().
	 *
	 */
	function get_input_html() {

		if ( method_exists( $this->field, 'get_input_html' ) ) {

			$input_html = $this->field->get_input_html();

		} else {

			$input_html = $this->input_feature()->get_feature_html();

		}

		return $input_html;
	}

	/**
	 * @return WP_Feature_Base
	 */
	function input_feature() {

		if ( ! isset( $this->features['input'] ) ) {

			// Do this to ensure the return value of input_feature() can be dereferenced. Should never be needed.
			$this->features['input'] = new WP_Field_Input_Feature( $this->field->view );

		}

		return $this->features['input'];

	}

	/**
	 * Delegate accesses for missing poperties to the $field property
	 *
	 * @param string $property_name
	 *
	 * @return mixed
	 */
	function __get( $property_name ) {

		return isset( $this->features[ $property_name ] ) ? $this->features[ $property_name ] : ( property_exists( $this->field,
				$property_name ) ? $this->field[ $property_name ] : null );

	}

	/**
	 * Delegate accesses for missing poperties to the $field property
	 *
	 * @param string $property_name
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	function __set( $property_name, $value ) {

		return isset( $this->features[ $property_name ] ) ? $this->features[ $property_name ] = $value : ( property_exists( $this->field,
				$property_name ) ? $this->field->$property_name = $value : null );

	}

	/**
	 * Delegate calls for missing methods to the $field property
	 *
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return mixed
	 */
	function __call( $method_name, $args = array() ) {

		return method_exists( $this->field, $method_name ) ? call_user_func_array( array(
				$this->field,
				$method_name
		), $args ) : parent::__call( $method_name, $args );

	}

	/**
	 * @param string $property_name
	 *
	 * @return bool
	 */
	function __isset( $property_name ) {

		return isset( $this->features[ $property_name ] );

	}

}

/**
 * Class WP_Feature_Base
 */
abstract class WP_Feature_Base extends WP_View_Base {

	/**
	 * @var string
	 */
	var $feature_type;

	/**
	 * @var WP_Field_Base
	 */
	var $field;

	/**
	 * @var WP_Field_View_Base
	 */
	var $view;

	/**
	 * @param WP_Field_View_Base $view
	 * @param array $feature_args
	 */
	function __construct( $view, $feature_args = array() ) {

		$this->field = $view->field;
		$this->view  = $view;

		parent::__construct( $feature_args );

		$this->owner = $this->field;

	}

	/**
	 * @return array
	 */
	static function CLASS_VALUES() {
		return array(
				'parameters' => array(
						'$value',
						'$parent',
						'$args',
				)
		);
	}

	/**
	 * @return array
	 */
	static function PROPERTIES() {

		return array(
				'field' => array( 'type' => 'WP_Field_Base', 'auto_create' => false ),
		);

	}

	/**
	 * Returns a new instance of a Field Feature object.
	 *
	 * @param string $feature_type
	 * @param WP_Field_View_Base $view
	 * @param array $feature_args
	 *
	 * @return null|WP_Feature_Base
	 */
	static function make_new( $feature_type, $view, $feature_args = array() ) {

		if ( $feature_type_class = WP_Metadata::get_feature_type_class( $feature_type ) ) {

			$feature_args['feature_type'] = $feature_type;

			$feature = new $feature_type_class( $view, $feature_args );

		} else {

			$feature = null;

		}

		return $feature;

	}

	/**
	 * @return bool|string
	 */
	function initial_element_id() {

		return str_replace( '_', '-', $this->field_name() ) . "-field-{$this->feature_type}";

	}

	/**
	 *  Used in initial_*() functions above.
	 */
	function field_name() {

		return $this->field->field_name;

	}

	/**
	 * @return bool|string
	 */
	function initial_element_class() {

		return "feature field-{$this->feature_type}";

	}

	/**
	 * @return bool|string
	 */
	function initial_element_name() {

		return 'wp_metadata_forms[' . $this->field->form_element_name() . '][' . $this->field_name() . ']';

	}

	/**
	 * @return string
	 */
	function get_feature_html() {

		return $this->get_html();

	}

	/**
	 * @param string $property_name
	 *
	 * @return mixed
	 */
	function __get( $property_name ) {

		$view = $this->view;

		if ( isset( $view->features[ $property_name ] ) ) {

			$value = $view->features[ $property_name ];

		} else {

			$value = parent::__get( $property_name );

		}

		return $value;

	}

	/**
	 * @param string $property_name
	 * @param mixed $value
	 */
	function __set( $property_name, $value ) {

		$this->view->features[ $property_name ] = $value;

	}
}

/**
 * Class WP_Form_View_Base
 */
abstract class WP_Form_View_Base extends WP_View_Base {

	/**
	 * @var string
	 */
	var $view_type;

	/**
	 * @var WP_Form
	 */
	var $form;

	/**
	 * @var WP_Html_Element
	 */
	var $wrapper;

	/**
	 * @var WP_Html_Element
	 */
	var $element;

	/**
	 * @param string $view_type
	 * @param string $form
	 * @param array $view_args
	 *
	 */
	function __construct( $view_type, $form, $view_args = array() ) {

		$view_args['view_type'] = $view_type;

		$this->form = $form;

		parent::__construct( $view_args );

		$this->owner = $form;

	}

	/**
	 * @return array
	 */
	static function CLASS_VALUES() {
		return array(
				'parameters' => array(
						'$value',
						'$parent',
						'$args',
				)
		);
	}

	/**
	 * @return array
	 */
	static function PROPERTIES() {

		return array(
				'form' => array( 'type' => 'WP_Form', 'auto_create' => false ),
		);

	}

	/**
	 * @param string $view_type
	 * @param string $form
	 * @param array $view_args
	 *
	 * @return WP_Form_View
	 *
	 */
	static function make_new( $view_type, $form, $view_args = array() ) {

		$form_view = new WP_Form_View( $view_type, $form, $view_args );

		return $form_view;

	}

	/**
	 * Convenience so users can use a more specific name than get_html().
	 *
	 * @return string
	 */
	function get_form_html() {

		return $this->get_html();

	}

	/**
	 * @return string
	 */
	function get_element_html() {

		return $this->get_form_fields_html();
	}

	/**
	 * @return string
	 */
	function get_form_fields_html() {

		$fields_html = array();

		/**
		 * @var WP_Field_Base $field
		 */
		foreach ( $this->form->fields as $field_name => $field ) {

			$fields_html[] = $field->view->get_field_html();

		}

//		$form_field = new WP_Hidden_Field( "wp_metadata_forms", array(
//			'value' => $this->form->form_name,
//			'storage' => 'memory',
//			'view' => 'hidden',
//			'shared_name' => true,
//			'form' => $this->form,
//		));
//
//		$fields_html[] = $form_field->get_field_html();

		return implode( "\n", $fields_html );

	}

	/**
	 * @return bool|string
	 */
	function initial_element_id() {

		return str_replace( '_', '-', "{$this->form->form_name}-metadata-form" );

	}

	/**
	 * @return bool|string
	 */
	function initial_element_class() {

		return "metadata-form";

	}

//	/**
//	 * @return bool|string
//	 */
//	function initial_element_id() {
//
//		return str_replace( '_', '-', $this->element->get_name() ) . '-' . $this->element->get_class();
//
//	}

}
