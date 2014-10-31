<?php
/*
 * Core Classes for WP-Metadata
 */

/**
 * Class WP_Object_Type
 *
 * Object Type is an infrastructure class used to classify fields and forms with
 * other potential classification use cases in the future.
 *
 * Registered fields are often intended to be for specific post types and as such
 * post types need to be specified. However, Fields should not be specific to
 * post types as fields would be beneficial for users, comments, options, and more.
 *
 * So the Object Type was designed to capture and allow developers to specify both
 * the $class of object (i.e. 'post', 'user', 'comment', etc.) as well as the $subtype
 * specific to the class, (i.e. 'post', 'page', 'attachment', etc. for Object Types
 * of $class 'post.')
 *
 * Object Types literals are specified in string form with a colon separating $class
 * from $subtype, which looks like this:
 *
 *    'post:post'
 *    'post:page'
 *    'post:attachment'
 *    'post:my_post_type'
 *
 * Object can be comparied with $object_type where is_object($object_type) is
 * true (because of the Object Type's __toString() method.):
 *
 *    $object_type = new WP_Object_Type( 'post:my_post_type' );
 *
 *    if ( 'post:my_post_type' == $object_type ) {
 *       echo 'They *are* equal!'
 *    }
 *
 * The 'any' subtype will match any item of the specified $class, and if a trailing
 * colon is used and subtype is ommitted then it implies 'any'. Both of these are
 * equivalent:
 *
 *    'post:any'
 *    'post:'
 *
 * If the colon is ommitted from an Object Type string literal then the Object Type's
 * $class is assumed to be 'post'
 *
 *    $object_type = new WP_Object_Type( 'my_post_type' );
 *
 *    if ( 'post:my_post_type' == $object_type ) {
 *       echo 'This is equal too.'
 *    }
 *
 * To specify any other $subtype besides 'post' requires a colon. If there is no
 * $subtype then it requires a trailing colon:
 *
 *    'user:'
 *    'comment:'
 *    'option:'
 *    'site_option:'
 *
 * Object Types can be reused by using the assign() method:
 *
 *    $post_types = get_post_types( array( '_builtin' => false ) );
 *    $object_type = new WP_Object_Type()
 *    foreach( $post_types as $post_type ) {
 *        $object_type->assign( $post_type );
 *        // Do something with $object_type
 *    }
 *
 * It's also possible to instantiate an Object Type with an associative array:
 *
 *    $object_type = new WP_Object_Type( array(
 *      'class' => 'post',
 *      'subtype' => 'my_post_type',
 *    );
 *
 */
final class WP_Object_Type {

	/**
	 * List of Object Type $class values recognized by WordPress core
	 *
	 * The array keys are the type revelent to the Object Type's $class, and t
	 * the values array() is designed to contain are a list of $args that are
	 * relevant to the Object Type $class.
	 *
	 * At this time the array() $args is not used, but reserved for future use.
	 *
	 * @todo Complete this list.
	 *
	 * @var array
	 */
	protected static $_object_type_classes = array(
			'post'    => array(),
			'user'    => array(),
			'comment' => array(),
		  'option' => array(),
		  'site_option' => array(),
		  // @todo And more
	);

	/**
	 * The $class property is used to contain the class of object such as 'post',
	 * 'user', 'comment', 'option', etc.
	 *
	 * @var null|string
	 */
	var $class = null;

	/**
	 * The $subtype property is to contain the 'type' relevant to the Object Type's
	 * $class, i.e. for 'post' there is 'post', 'page', 'attachment' and whatever
	 * custom post types have been defined.
	 *
	 * For $class values of 'user' we are currently assuming role will used for $subtype.
	 *
	 * For all other $class values the value of $subtype is TBD.
	 *
	 * @var null|string
	 */
	var $subtype = null;

	/**
	 * Initialize an Object Type object with an optional Object Type literal string passed
	 * in to represent the object type:
	 *
	 * @example
	 *
	 *    $object_type = new WP_Object_Type( 'my_post_type' );
	 *
	 * An Object Type can effectively be cloned by passing an Object Type object instead
	 * of a literal, i.e.
	 *
	 *    $object_type = new WP_Object_Type( 'my_post_type' );
	 *    $2nd_object_type = new WP_Object_Type( $object_type );
	 *
	 *    if ( (string)$object_type == (string)$2nd_object_type ) {
	 *       echo 'This is equal.'
	 *    }
	 *    if ( 'post:my_post_type' == $object_type ) {
	 *       echo 'And this is equal.'
	 *    }
	 *    if ( 'post:my_post_type' == $2nd_object_type ) {
	 *       echo 'And this is also equal.'
	 *    }
	 *    if ( $object_type === $2nd_object_type ) {
	 *       echo 'But this is NOT equal.'
	 *    }
	 *
	 * Passing in an object is useful in functions that might be called directly by
	 * a developer with an object literal but might be also called indirectly where
	 * the Object Type literal string had already been replaced with its object
	 * equivalent.
	 *
	 * It's also possible to instantiate an Object Type with an associative array:
	 *
	 *    $object_type = new WP_Object_Type( array(
	 *      'class' => 'post',
	 *      'subtype' => 'my_post_type',
	 *    );
	 *
	 * @param bool|string|array|object $object_type
	 */
	function __construct( $object_type = false ) {

		if ( $object_type ) {

			$this->assign( $object_type );

		}

	}

	/**
	 * Validates and assigns a value to this Object Type
	 *
	 * @example:
	 *
	 *    $object_type = new WP_Object_Type();
	 *    $object_type->assign( 'my_post_type' )
	 *
	 * Which is equivalent to:
	 *
	 *    $object_type = new WP_Object_Type( 'my_post_type' )
	 *
	 * @param bool|string|array|WP_Object_Type $object_type
	 */
	function assign( $object_type = false ) {

		if ( empty( $object_type ) ) {
			/**
			 * If an empty Object Type is passed assume it's of $class == 'post' and
			 * $subtype == $post->post_type.
			 */
			global $post;

			$object_type = self::get_post_object_type_literal( $post->post_type );

		}

		if ( is_a( $object_type, __CLASS__ ) ) {
			/**
			 * If a WP_Object_Type object was passed in then copy it's values.
			 *
			 * @see The PHPDoc for __construct() to understand why accepting an
			 *      object in addition to a string literal is useful.
			 */
			$this->class   = $object_type->class;
			$this->subtype = $class->subtype;

		} else {

			if ( is_string( $object_type ) ) {
				/**
				 * When an Object Type string literal is passed...
				 */
				if ( false === strpos( $object_type, ':' ) ) {

					/**
					 * And the literal contains no colon, assume the $class is 'post'
					 * and the string literal passed in is the $subtype (often used
					 * with custom post types.)
					 */
					$this->class   = 'post';
					$this->subtype = $object_type;

				} else {

					/**
					 * Otherwise split the Object Type literal on a the colon and assign
					 * to $class and $subtype, respectively.
					 */
					list( $this->class, $this->subtype ) = explode( ':', $object_type );

				}

			} else if ( is_array( $object_type) && 2 == count( $object_type ) && isset( $object_type[0] ) && isset( $object_type[1] ) ) {

				/**
				 * A 2 element numerically indexed array where the first element is $class and the 2nd is $subtype.
				 * So assign it.
				 */

				list( $this->class, $this->subtype ) = $object_type;

				/*
				 * Initialize the class property, defaulting to 'post' if empty.
				 */
				if ( empty( $this->class ) ) {

					$this->class = 'post';

				}

			} else {

				/*
				 * Assumes the $object_type passed in is either an associative array with 'class'
				 * and 'subtype' properties or an object that is not of class WP_Object_Type with
				 * $class and $subtype properties
				 *
				 * Not sure why an object not of type WP_Object_Type would ever be needed, but if
				 * someone finds a need for it this this method will support initialized from its
				 * property values.
				 */
				if ( ! is_array( $object_type ) ) {
					/*
					 * Convert the array to object so the same code can initialize if an array or an
					 * object is passed in.
					 */
					$object_type = (object)$object_type;
				}

				/*
				 * Initialize the class property, defaulting to 'post' if empty.
				 */
				$this->class = empty( $object_type->class ) ? $object_type->class : 'post';

				/*
				 * Initialize the subtype property, defaulting to false if empty.
				 */
				$this->subtype = empty( $object_type->subtype ) ? $object_type->subtype : false;
			}

			/**
			 *  Ensure $class is sanitized to be a valid identifier
			 */
			$this->class = WP_Metadata::sanitize_identifier( $this->class );

			if ( $this->subtype ) {
				/**
				 *  Ensure $subtype is sanitized to be a valid identifier too, but only need to do if not empty.
				 */
				$this->subtype = WP_Metadata::sanitize_identifier( $this->subtype );

			}
		}

		if ( empty( $this->subtype ) ) {
			/**
			 * Lastly, if $subtype is still empty, set to 'any'.
			 */
			$this->subtype = 'any';

		}

	}

	/**
	 * Register a new Object Type $class.
	 *
	 * Allows a plugin or theme to register it' own $class values for Object Types.
	 *
	 * An example might be for a plugin we call 'Awesome Event Calendar', it might
	 * register a new Object Type $class of 'aec_event' where 'aec_' is the plugin's
	 * prefix:
	 *
	 *    register_object_type_class( 'aec_event' );
	 *
	 * This would allow developers to register fields for an 'aec_event'.
	 * HOWEVER, an event would probably best be a custom post type so this functionality
	 * may be rarely used, if ever.  Still, it's here if it is needed.
	 *
	 * The $args array is currently unused but here for future needs.
	 *
	 * $class values cannot be registered twice
	 *
	 * @param string $class The new Object Type $class to register.
	 * @param array $class_args The $args for the registered $class. Currently unused.
	 *
	 * @return bool Whether the object type $class was registered. \
	 */
	public static function register_class( $class, $class_args = array() ) {

		if ( ! isset( self::$_object_type_classes[ $class ] ) ) {

			self::$_object_type_classes[ $class ] = $class_args;

			return true;

		}

		return false;

	}

	/**
	 * Get an unqualified type string for generating simplified output when context is not needed.
	 *
	 * Gets the $subtype unless $class is 'any' or empty.
	 *
	 * @return string
	 */
	function unqualified_type() {

		return empty( $this->subtype ) || 'any' == $this->subtype ? $this->class : $this->subtype;

	}

	/**
	 * Check if the current Object Type is valid.
	 *
	 * Validity is determined by having a non-empty $class value.
	 *
	 * @return bool Is the Object Type valid?
	 */
	public function is_valid() {

		return ! empty( $this->class );

	}

	/**
	 * Check if the current Object Type is equivalent to the one passed in.
	 *
	 * Equivalency is true if both objects have the same values for their $class and $subtype properties.
	 *
	 * If not parameter is passed then this method assume an object type based on the global $post object.
	 *
	 * @param false|WP_Object_Type|string $object_type The Object Type to compare with $this.
	 *
	 * @return bool If $object_type is equivalent to $this.
	 */
	public function is_equivalent( $object_type = false ) {

		if ( ! is_a( $object_type, __CLASS__ ) ) {
			/*
			 * First check to see if the passed in parameter is a WP_Object_Type object.
			 * If not, instantiate a new object with the passed $arg.
			 */
			$object_type = new self( $object_type );

		}

		/**
		 * Check for object equivalency
		 * Yes this is correct (if you thought it was not, like I did at first.)
		 */
		return $this == $object_type;

	}

	/**
	 * Returns an Object Type literal string given a post type
	 *
	 * @param string $post_type Post type to generate an Object Type literal string.
	 *
	 * @return string An Object Type literal.
	 *
	 * @todo Should $post_type be validated to exist?
	 *
	 */
	static function get_post_object_type_literal( $post_type ) {

		return $post_type ? "post:{$post_type}" : 'post:any';

	}

	/**
	 * Returns an Object Type given a post type
	 *
	 * @param string $post_type Post type to generate an Object Type.
	 *
	 * @return string An Object Type.
	 *
	 * @todo Should $post_type be validated to exist?
	 *
	 */
	static function get_post_object_type( $post_type ) {

		return new self( self::get_post_object_type_literal( $post_type ) );

	}

	/**
	 * Magic method to convert the Object Type into it's string literal form.
	 *
	 * @return string  An Object Type literal representing $this, the current Object Type.
	 */
	function __toString() {

		return "{$this->class}:{$this->subtype}";

	}

}

/**
 * Class WP_Html_Element
 *
 * Class used to generate an HTML element, including contained elements, if applicable.
 *
 * This class allows an associative array of $args containing attributes and values to be
 * passed down through functions that call other functions, some of which may set default
 * $arg values, where the $args are ultimately designed to generate HTML.
 *
 * As an example, a developer can call register_post_field() and pass a value of 10 for
 * the $field_args key of 'view:features[input]:element:size' and that allows the HTML
 * Element to receive an array with a key/value of 'size'=>10 in the $attributes array
 * passed to the HTML Element's __construct() method.
 *
 * This if fields use HTML Element objects to construct the HTML elements needed for output
 * no extra work needs to be done to support initialization of any property that is valid
 * for the element type identified by the $tag_name property.
 *
 * Class does not (currently?) contain child HTML Elements; its $value property
 * should contain the generated text of the child HTML Elements.  This may change
 * if we discover the need for containing children.
 *
 * Designed for HTML5.  Not designed to generate valid XHTML.
 *
 */
final class WP_Html_Element {

	/**
	 * Provide list of HTML element names that do not need a closing tag.
	 * Used to ensure proper generation of HTML element.
	 *
	 * @const string
	 */
	const _VOID_ELEMENTS = 'area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr';

	/**
	 * The HTML Tag Name, such as 'div', 'input', 'label', or 'textarea'
	 *
	 * @var string
	 */
	var $tag_name;

	/**
	 * The value to use when generating HTML element.  If $tag_name contains a void tag then this
	 * $value is used for the 'value' attribute otherwise it's used as the inner text of the element.
	 *
	 * To generate an HTML element that contains other HTML elements, set this property with the text
	 * string that contains the HTML elements.
	 *
	 * Can be a callable that will generate the value.
	 *
	 * @var null|string|callable
	 */
	var $value;

	/**
	 * Contains names and values of the HTML element attributes.
	 *
	 * @var array
	 */
	protected $_attributes;

	/**
	 * Construct a new HTML Element object.
	 *
	 * @param string $tag_name The HTML Element tag name, i.e. 'div', 'input', 'label', or 'textarea'.
	 * @param array $attributes An associative array containing attribute names and their values
	 * @param null|callable|string $value The value of the innner text property or of the 'value' attribute.
	 */
	function __construct( $tag_name, $attributes = array(), $value = null ) {

		/**
		 * assign() initializes the properties of the HTML Element.
		 */
		$this->assign( $tag_name, $attributes, $value );

	}

	/**
	 * Define the parameters needs for the make_new() Factory menthod for this class.
	 * @return array
	 */
	static function CLASS_VALUES() {
		return array(
				'parameters' => array(
						'html_tag',
						'$value',
						null,
				)
		);
	}

	/**
	 * Factory method for WP_Html_Element
	 *
	 * @param string $tag_name
	 * @param array $attributes
	 * @param null|callable|string $value
	 *
	 * @return self
	 */
	static function make_new( $tag_name, $attributes = array(), $value = null ) {

		return new self( $tag_name, $attributes, $value );

	}

	/**
	 * Used to initialize an HTML Element with it's constructor parameters.
	 *
	 * Also useful to allow HTML Elements to be reused rather than having to create a new one
	 * where the HTML Element object is just being used for output, in a loop for example.
	 *
	 * @param string $tag_name
	 * @param array $attributes
	 * @param null|callable|string $value
	 */
	function assign( $tag_name, $attributes = array(), $value = null ) {

		$this->tag_name = $tag_name;
		if ( is_null( $attributes ) ) {
			$attributes = array();
		}
		$this->_attributes = wp_parse_args( $attributes );
		$this->value       = $value;

	}

	/**
	 * Generate the HTML string for this HTML Element, its attributes, and its contained value if applicable.
	 *
	 * @return string
	 */
	function get_html() {

		/**
		 * Sanitize this tag_name to ensure
		 */
		if ( ! ( $tag_name = WP_Metadata::sanitize_identifier( $this->tag_name ) ) ) {

			/*
			 * If tag name is empty after sanitization, it's not a valid tag name.
			 * Provide some type of indicator of value in the generated HTML.
			 *
			 * @todo Figure out how to sanitize it enough so we can debugging output.
			 *
			 */

			$html = "<!-- invalid WP_Html_Element->tag_name -->";

		} else {

			/*
			 * Build the HTML Element's opening tag.
			 */
			$html = "<{$tag_name} " . $this->get_attributes_html() . '>';

			if ( ! $this->is_void_element() ) {
				/*
				 * If not a void element and
				 */
				if ( is_callable( $this->value ) ) {

					/*
					 * Call the callable to generate the value.
					 * Pass in $this so it has some context.
					 */
					$value = call_user_func( $this->value, $this );

				} else {

					/*
					 * Just grab the value.
					 */
					$value = $this->value;

				}

				/*
				 * Append the inner text value and the closing tag.
				 */
				$html .= "{$value}</{$tag_name}>";
			}

		}
		return $html;

	}

	/**
	 * Generate the HTML that for the names and attributes inside an HTML element's opening tag.
	 *
	 * Only generates name/value pairs for attributes that are valid for the HTML element type
	 * as indicated by tag_name.
	 *
	 * @example output (sans single quotes):
	 *
	 *    'attr1="value1" attr2="value2" attr3="value3"';
	 *
	 * @return string
	 */
	function get_attributes_html() {

		/*
		 * Get the valid attributes for this HTML element by tag name.
		 */
		$valid_attributes = WP_Metadata::get_html_attributes( $this->tag_name );

		/*
		 * Remove any attributes empty attributes.
		 */
		$attributes = array_filter( $this->_attributes );

		$html = array();

		if ( isset( $valid_attributes['value'] ) && $this->is_void_element() ) {
			/*
			 * If this has a value set and it's a void element, be sure to sanitize it.
			 */
			$attributes['value'] = esc_attr( $this->value );
		}

		/*
		 * Loop through each of the attributes
		 */
		foreach ( $attributes as $name => $value ) {
			if ( false !== $value && ! is_null( $value ) && isset( $valid_attributes[ $name ] ) ) {
				/*
				 *  Include if the attribute has a value and is valid for this HTML Element type
				 */
				if ( $name = WP_Metadata::sanitize_identifier( $name ) ) {
					/**
					 * Is the name provided can be sanitized (because if not the sanitize_identifier() returns a null)
					 * add this name/value pair to the $html array.
					 */
					$value  = esc_attr( $value );
					$html[] = "{$name}=\"{$value}\"";
				}
			}
		}

		/*
		 * Merge array of formatted attribute name/value pairs into an HTML string.
		 */
		return implode( ' ', $html );

	}

	/**
	 * Acess the internal associative array containing the names and value of the attributes
	 * for this HTML Element.
	 *
	 * @return array
	 */
	function attributes() {

		return $this->_attributes;

	}

	/**
	 * Tests the current $tag_name to determine if it represents an HTML5 element that does
	 * not require a closing tag.
	 *
	 * @return bool
	 */
	function is_void_element() {

		return preg_match( '#^(' . self::_VOID_ELEMENTS . ')$#i', $this->tag_name ) ? true : false;

	}

	/**
	 * Shortcut to access the value for the 'id' attribute of an HTML Element.
	 *
	 * @return string
	 */
	function get_id() {

		return $this->get_attribute_value( 'id' );

	}

	/**
	 * Shortcut to access the value for the 'name' attribute of an HTML Element.
	 *
	 * @return string
	 */
	function get_name() {

		return $this->get_attribute_value( 'name' );

	}

	/**
	 * Shortcut to access the value for the 'class' attribute of an HTML Element.
	 *
	 * @return string
	 */
	function get_class() {

		return $this->get_attribute_value( 'class' );

	}

	/**
	 * Shortcut to access the  value for any attribute of an HTML Element.
	 *
	 * @param string $attribute_name
	 *
	 * @return mixed
	 */
	function get_attribute_value( $attribute_name ) {

		return ! empty( $this->_attributes[ $attribute_name ] )
			? trim( $this->_attributes[ $attribute_name ] )
			: false;

	}

	/**
	 * Shortcut to set a value for the HTML Element's 'id' attribute.
	 *
	 * @param $value
	 */
	function set_id( $value ) {

		$this->set_attribute_value( 'id', $value );

	}

	/**
	 * Shortcut to set a value for the HTML Element's 'name' attribute.
	 *
	 * @param $value
	 */
	function set_name( $value ) {

		$this->set_attribute_value( 'name', $value );

	}

	/**
	 * Shortcut to set a value for the HTML Element's 'class' attribute.
	 *
	 * @param $value
	 */
	function set_class( $value ) {

		$this->set_attribute_value( 'class', $value );

	}

	/**
	 * Shortcut to set a value for any HTML Element's attribute.
	 *
	 * @param string $attribute_name
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	function set_attribute_value( $attribute_name, $value ) {

		if ( false === $value || is_null( $value ) ) {

			unset( $this->_attributes[ $attribute_name ] );

		} else {

			$this->_attributes[ $attribute_name ] = $value;

		}

		return $value;

	}

	/**
	 * Shortcut to append a value to the HTML Element's existing value for its 'class' attribute.
	 *
	 * @param $value
	 */
	function append_class( $value ) {

		$this->append_attribute_value( 'class', $value );

	}

	/**
	 * Appends a value to the end of an attribute's existing value.
	 *
	 * Useful to add another selector to a 'class' attribute, see $this->append_class().
	 *
	 * @param string $attribute_name Name of a valid attribute for the element type as defined by $tag_name.
	 * @param mixed $value Value to append to the element.
	 * @param string $separator
	 *
	 */
	function append_attribute_value( $attribute_name, $value, $separator = ' ' ) {

		if ( false !== $value && ! is_null( $value ) ) {

			if ( isset( $this->_attributes[ $attribute_name ] ) ) {

				$this->_attributes[ $attribute_name ] = trim( "{$this->_attributes[$attribute_name]}{$separator}{$value}" );

			} else {

				$this->_attributes[ $attribute_name ] = $value;

			}

		}

	}

}

/**
 * Class WP_Annotated_Property
 *
 * Class that enabledsuses of 'annotations' of class properties similar to Annotations in Java
 *
 * @see: http://tutorials.jenkov.com/java/annotations.html
 *
 * Annotations are useful because they allow programmers to develop generic logic that can
 * process a collection of classes using the data in the annotation with one set of generic
 * code instead of having to hardcode logic for each unique type of class. In the example shown
 * above you can see 'type' which allows WP_Field_Base's parent::_construct() method to auto
 * create instances for the properties that have been annotated with a class for 'type', assuming
 * that the 'auto_create' annotation has not been set to false.
 *
 * This class is used to represent the 'annotations' for a single property on another class.
 * Any PHP class can define annotations for itself by declaring a PROPERTIES() static function
 * that returns an array of arrays where the keys of the array are the names of the properties as
 * declared in the class.
 *
 * @example From the WP_Field_Base class:
 *
 *		static function PROPERTIES() {
 *
 *			return array(
 *				'value'   => array( 'type' => 'mixed' ),
 *				'field_group'    => array( 'type' => 'WP_Field_Group', 'auto_create' => false ),
 *				'view'    => array( 'type' => 'WP_Field_View_Base' ),
 *			);
 *
 *		}
 *
 * Properties in a class are not required to be annotated but if they are then the literal
 * form of an annotation typed by a developer will an $args array similar in format to the $args
 * array passed to register_post_type().
 *
 * The $args array will be passed the 2nd parameter of WP_Annotated_Property->__contruct(), the
 * first parameter being the name of the property as defined in the class (although you rarely
 * if ever need to instantiate yourself; it'll be done automatically fot you):
 *
 *     $name = 'field_group';
 *     $args = array( 'type' => 'WP_Field_Group', 'auto_create' => false ),
 *     $property = new WP_Annotated_Property( $name, $field_group );
 *
 * The main use of this class within WP_Metadata is in ::get_property_annotations( $class_name )
 * where each sub-array of 'annotation' arrays is passed into an object of type
 * WP_Annotated_Property. The array if 'annotation' arrays is returned by the PROPERTIES() method.
 *
 */
final class WP_Annotated_Property {

	/**
	 * @var array List of default annotations by class.
	 *
	 * For example a default annotation of WP_Html_Class->html_tag is 'div' and is specified like so:
	 *
	 * 		WP_Metadata::register_default_annotations( 'WP_Html_Element', array(
	 *			'html_tag' => 'div'
	 *    ) );
	 *
	 * Modified by self::register_default_annotations()
	 * Accessed by self::get_default_annotations()
	 *
	 */
	static $_default_annotations = array();

	/**
	 * @var string The name of the property for the associated class for which the annotations apply.
	 */
	var $property_name;

	/**
	 * @var string The "Type" of the property for the associated class for which the annotations apply.
	 *
	 * Types are class names, arrays of class names (denoted as "Class_Name[]"), scalar types (i.e. 'int',
	 * 'string', etc.) or arrays of scalar types, i.e 'int[]', 'string[]', etc.
	 * @example 'Class_Name', 'Class_Name[]', 'int', 'string[]', etc.
	 */
	var $property_type;

	/**
	 * @var string Value to capture the "Type" of array eleemnts when a 'type' value passed in as an
	 *             $arg to __construct()contains trailing open/close square brackets ('[]').
	 *
	 * If 'WP_Html_Element[]' is passed to __construct() then $array_of will get' WP_Html_Element'
	 * and $property_type will get set to 'array'.
	 *
	 * @example 'Class_Name', 'int', etc.
	 */
	var $array_of = null;

	/**
	 * @var mixed Value to assign the property when no value is passed to __construct() for the property.
	 */
	var $default;

	/**
	 * @var array Allows for overriding the 'parameters' in CLASS_VALUES() in the case it is needed.
	 *
	 * @todo Need to find a use-case before implementing this.
	 */
	var $parameters = array();

	/**
	 * @var string The prefix used by the declaring class for properties of a contained class.
	 *
	 * For example, the WP_Input_Feature class has an $element property designed to contain an
	 * WP_Html_Element object thus its prefix is 'element' and that allows the Input Feature class
	 * to be instantiated with an $arg like 'element:size' and thus pass the value of 'size' to the
	 * __construct() method of WP_Html_Element when instantiating.
	 *
	 * @example
	 *
	 *    'field_group', 'field', 'storage', etc.
	 */
	var $prefix;

	/**
	 * @var bool When true the __construct() method for the WP_Metadata_Base class -- which this
	 *           class extends from -- will attempt to instantiate and assign any property defined
	 *           have it's type be a class or array of classes.  If false, the assumption is made
	 *           the the property will be assigned manually in code somewhere.
	 */
	var $auto_create = true;

	/**
	 * @var array An array to contain any 'custom' values passed in that might be needed beyond those
	 *            that are predefined in this class.
	 *
	 * To limit complexity we decided this class would declared 'final' and thus mostly just be used
	 *            as as a repository of data and not be able to be subclassed. This $custom is a
	 *            properly that allows some extensibility by allow any data to be added to properties
	 *            but in a way that won't impeded future enhancement to this class.
	 */
	var $custom = array();

	/**
	 * @var string Contains the $name of a registry that is passed to the __construct() method for
	 *             WP_Registry. It will refer to a registry that contains the keys for an array
	 *             property.
	 *
	 * For example, the 'feature_type' registry contains the applicable keys for the $features
	 *             array of the WP_Field_View_Base class. This allows WP_Metadata_Base to know
	 *             how to initialize those properties.
	 *
	 * @example
	 *
	 *    'field_type', ''feature_type', etc.
	 */
	var $registry;

	/**
	 * @var array Contains the list of valid keys for an parperty defined as an array.
	 *
	 * For example, the WP_Field_View_Base class declares the keys 'label', 'input', 'help', 'message'
	 * and 'infobox' for the property $features.
	 *
	 */
	var $keys = array();

	/**
	 * Instantiate an Annotated Property object.
	 *
	 *
	 *
	 * @param string $property_name Name of the associated property of the contained class
	 * @param mixed[] $annotations List of name/value pair annotations for the property.
	 */
	function __construct( $property_name, $annotations ) {

		/**
		 * Capture the property name first.
		 */
		$this->property_name = $property_name;

		/**
		 * Now determine the Type of the property
		 */
		if ( empty( $annotations['type'] ) ) {
			/*
			 * Default to 'mixed' if not declared.
			 */
			$this->property_type = 'mixed';
		} else if ( preg_match( '#(.+)\[\]$#', $annotations['type'], $match ) ) {
			/*
			 * In case of array, designed by trailing brackets e.g. '[]',
			 * set type = 'array' and 'array_of' to the type specified.
			 */
			$this->property_type = 'array';
			$this->array_of      = $match[1];
		} else {
			/*
			 * Otherwuise set $property_type to what was specified in the 'type' annotation.
			 */
			$this->property_type = $annotations['type'];
		}
		/**
		 * Now unset this because we no longer need it and don't want to add to the $custom array.
		 */
		unset( $annotations['type'] );

		if ( class_exists( $this->property_type ) || ( $this->is_array() && class_exists( $this->array_of ) ) ) {
			/*
			 * If a class was specified either singularly or as an array of...
			 */
			if ( ! isset( $annotations['prefix'] ) ) {

				/*
				 *  Default 'prefix' to property name, for convenience.
				 */
				$this->prefix = $property_name;
			}

			/*
			 *  Now merge in the default annotations that were registered for the type
			 *  using register_default_annotations() method of this class.
			 *
			 * For example WH_Html_Element has a defailt annotation for 'html_tag' set to 'div':
			 *
			 *			self::register_default_annotations( 'WP_Html_Element', array(
			 *					'html_tag' => 'div'
			 *			));
			 *
			 */
			$annotations = array_merge(
					self::get_default_annotations( $this->property_type ),
					$annotations
			);

		}

		foreach ( $annotations as $arg_name => $arg_value ) {
			/*
			 * Now assign the remaining annotations to either...
			 */
			if ( property_exists( $this, $arg_name ) && 'custom' != $arg_name ) {

				/*
				 * ... the named property (unless it is 'custom'), or...
				 */
				$this->$arg_name = $arg_value;

			} else {

				/*
				 *  ... an array element of the $custom property.
				 */
				$this->custom[ $arg_name ] = $arg_value;

			}
		}

	}

	/**
	 * Access the value for a known annotation contained in $this WP_Annotated_Property object.
	 *
	 * First checks to see if $annotation_name is a property and if so, returns the property's value.
	 * Next checks to see if $annotation_name prefixed with 'property_' is a property and if so, returns the property's value.
	 * Next checks to see if removing a 'property_' prefix, if one exists, is a property and if so, returns the property's value.
	 * If none of these check out to be true, the function returns null.
	 *
	 * @param string $annotation_name Name of the annotation for which to return it's value.
	 *
	 * @return mixed|null The value of the annotation named as $annotation_name.
	 */
	function get_annotation_value( $annotation_name ) {

		if ( property_exists( $this, $annotation_name ) ) {
			/*
			 * Check to see if $annotation_name is a property and if so, returns the property's value.
			 */
			$annotation = $this->{$annotation_name};

		} else if ( property_exists( $this, $long_name = "property_{$annotation_name}" ) ) {
			/*
			 * Check to see if $annotation_name prefixed with 'property_' is a property and if so, returns the property's value.
			 */
			$annotation = $this->{$long_name};

		} else if ( property_exists( $this, $short_name = preg_replace( '#^property_(.+)$#', '$1', $annotation_name ) ) ) {
			/*
			 * Check to see if removing a 'property_' prefix, if one exists, is a property and if so, returns the property's value.
			 */
			$annotation = $this->{$short_name};

		} else {
			/*
			 * If none of these check out to be true, the function returns null.
			 */
			$annotation = null;

		}

		return $annotation;

	}

	/**
	 * Access the value for a custom annotation contained in $this WP_Annotated_Property object.
	 *
	 * Checks to see if such a custom property exists and if not returns null
	 *
	 * @param string $annotation_name Name of the annotation for which to return it's value.
	 *
	 * @return mixed|null The value of the custom annotation named as $annotation_name.
	 */
	function get_annotation_custom( $annotation_name ) {

		if ( isset( $this->custom[ $annotation_name ] ) ) {
			/**
			 * Check to see if such a custom property exists.
			 * If yes, return its value.
			 */
			$annotation = $this->custom[ $annotation_name ];

		} else {
			/**
			 * If no, return null.
			 */
			$annotation = null;

		}

		return $annotation;

	}

	/**
	 * Generic factory method to instantiate a new object as defined by the class name in $property_type.
	 *
	 * Calls the make_new() static factory method declared for the class in $property_type, if it exists.
	 *
	 * Assuming the make_new() method expects parameters the $class named in $property_type should have a
	 * CLASS_VALUES() method that returns an array with an element 'parameters' that itself is a simple
	 * array who values specify indicators for the args required, list in the same order that make_new()
	 * expects them.
	 *
	 * For example, the static factory method for WP_Field_Base has the following signature
	 *
	 *    static function make_new( $field_name, $object_type, $field_args = array() );
	 *
	 * And the class annotations for WP_Field_Base::CLASS_VALUES() contain a 'parameters' element like this:
	 *
	 *   	static function CLASS_VALUES() {
	 *
	 *		  return array(
	 *			  	'...' => array( ... ),
	 *				  'parameters' => array(
	 *					  	'$value',
	 *						  'object_type',
	 *  						'$args',
	 *	  			),
	 *		  	'...' => array( ... ),
	 * 		  );
	 *   }
	 *
	 * Thus:
	 *
	 *      - $field_name  gets the value of $args[$this->property_name] passed in from register_post_field() per '$value'
	 *      - $object_type gets the property value for $this->object_type per 'object_type'.
	 *      - $field_args  gets the $args array passed in from register_post_field() per '$args'.
	 *
	 * @see WP_Annotated_Property::build_parameters()
	 *
	 * @param array $object_args The context and other values needed to pass to the make_new() method.
	 *
	 * @return object|null The object instantiated by this factory, or null if object could not be instantiated.
	 *
	 */
	function make_object( $object_args ) {

		if ( $this->is_class() && method_exists( $this->property_type, 'make_new' ) ) {

			/*
			 * If this property represents an object class with a static method named make_new()
			 * then build the parameters needed to pass to make_new().
			 */
			$parameters = self::build_parameters( $this->property_type, $object_args );

			/*
			 * Call make_new() with the built parameters to generate an instance of the class named in $property_type.
			 */
			$object = call_user_func_array( array( $this->property_type, 'make_new' ), $parameters );

		} else {

			/*
			 * Property does not declare a class or the class has no make_new() method.
			 */
			$object = null;

		}

		return $object;

	}

	/**
	 * Test to see if the value in $property_type is a valid class name.
	 *
	 * @return bool
	 */
	function is_class() {

		return class_exists( $this->property_type );

	}

	/**
	 * Test to see if the value in $property_type represents an array.
	 *
	 * @return bool
	 */
	function is_array() {

		return 'array' == $this->property_type;

	}

	/**
	 * Register the default annotations for a 'type'.
	 *
	 * In the case of type=class_name, the registered default annotations apply to it's child classes.
	 *
	 * @param string $type Class name or other data type for which to register default annotations.
	 *
	 * @param array $default_values The array of default annotations to register.
	 */
	static function register_default_annotations( $type, $default_values ) {

		/*
		 * Store in an array by 'type', and provide logic to determine if the parent class
		 * annotations have been merged in yet or not via the 'cached' property.
		 */
		self::$_default_annotations[ $type ] = (object) array(
				'cached' => false,
				'values' => $default_values,
		);

	}

	/**
	 * Get default annotations for a 'type'
	 *
	 * If the first time accessed for a type, this function accesses annotations for all parent classes
	 * too and merges them in, finally caching them so the initial access need only be done one.
	 *
	 * @param string $type  Class name or other data type for which to get default annotations.
	 *
	 * @return array List of default annotations for the 'type' specified.
	 */
	static function get_default_annotations( $type ) {

		if ( ! isset( self::$_default_annotations[ $type ] ) ) {

			/*
			 * If the first time for this class, initialize a structure to contain the default values.
			 */
			self::$_default_annotations[ $type ] = (object) array(
					'cached' => false,
					'values' => array(),
			);

		}

		if ( ! self::$_default_annotations[ $type ]->cached ) {
			/*
			 * If default annotations not previouslty cached for this type,
			 * look at ancestor classes and merge in their default annotations
			 * too.
			 */

			if ( $parent = get_parent_class( $type ) ) {
				/**
				 * Annotation values for the same name from child classes override
				 * the value defined in a parent class.
				 */
				self::$_default_annotations[ $type ]->values = array_merge(
						self::get_default_annotations( $parent ),
						self::$_default_annotations[ $type ]->values
				);

			}

			/**
			 * Set 'cached' to indicate we don't need to do this again.
			 */
			self::$_default_annotations[ $type ]->cached = true;

		}

		/**
		 * Finally return the newly or previously cached defaul annotation values.
		 */
		return self::$_default_annotations[ $type ]->values;

	}

	/**
	 * Build the parameters needed for a classes' static make_new() object factory.
	 *
	 * @param string $class_name The name of the class to instantiate
	 * @param array $object_args The context values and $args passed (indirectly) to instantiate objects.
	 *
	 * @return array The array of parameters to pass to the class's static make_new() method.
	 */
	static function build_parameters( $class_name, $object_args ) {

		/*
		 * Start with an empty set of arguments.
		 */
		$parameters = array();

		/*
		 * Initialize a variable to keep track of the $args position in the array.
		 */
		$args_index = false;

		/*
		 * @todo Change all these to be stored in a '$context' array instead of stored inline.
		 *
		 * Get the 'make_new' parameter template for the named $class_name. Will return an array that may
		 * contain any of the following:
		 *
		 *  '$value' -    If included will refer to $object_args['$value'] set to be the value passed
		 *                in for the property. Typically this will be a registered name used to look
		 *                up more information in the registry.
		 *
		 *  '$parent' -   If included will refer to the parent object of the object that is calling make_new()
		 *                and its meaning is defined by whichever class uses it.
		 *
		 *  '$args' -     If included will refer to the entire list of $object_args elements minus any
		 *                ${context} args.
		 *
		 *  '$property' - If included will refer to the instance of this WP_Annotated_Property class that
		 *                will provide access to the custom
		 *
		 *  '{name}' -    If included then 'name' will refer to the value of $object_args['name'], if such an
		 *                element exists. Examples include 'object_type', 'view_type', etc.
		 */
		$make_new_parameters = WP_Metadata::get_make_new_parameters( $class_name );


		/*
		 * For each of the make_new() parameters needed...
		 */
		foreach ( $make_new_parameters as $parameter_name ) {

			if ( preg_match( '#^(\$value|\$parent)$#', $parameter_name ) ) {
				/*
				 * If $parameter_name is either '$value' or '$parent',
				 * just add one's value to $parameters array.
				 */
				$parameters[] = $object_args[ $parameter_name ];

			} else if ( '$args' == $parameter_name ) {
				/*
				 * If $parameter_name is '$args' then capture it's index in
				 * the $parameters array and then pass in all the $args.
				 */
				$args_index   = count( $parameters );
				$parameters[] = $object_args;

			} else if ( is_null( $parameter_name ) || is_bool( $parameter_name ) ) {
				/*
				 * If $parameter_name is null, false or true the class wants to skip
				 * that parameter, so just set as is.
				 */
				$parameters[] = $parameter_name;

			} else if ( isset( $object_args[ $parameter_name ] ) ) {
				/*
				 * If $parameter_name is a key for an element of the $object_args then
				 * use that element's value for the parameter.
				 */
				$parameters[] = $object_args[ $parameter_name ];

			} else if ( isset( $object_args['$property'] ) ) {

				/*
				 * Assume that there will be a default value for this $property_name
				 * in the 'custom' collection of the Annotated Property for the property
				 * that will get assigned theyet-to-be-built instance we are building
				 * the parameters for.
				 */
				$property_args = $object_args['$property']->custom;

				if ( isset( $property_args[ $parameter_name ] ) ) {
					/*
					 * If we assumed correctly and their was a matching 'custom' name
					 * in the Annotated Property then use it's value for the parameter
					 */

					$parameters[] = $property_args[ $parameter_name ];

				} else {

					/**
					 * Otherwise generate an error message to aid in debugging.
					 */
					$message = __( 'Unknown parameter %s for %s::make_new().', 'wp-metadata' );
					trigger_error( sprintf( $message, $parameter_name, $class_name ) );

				}

			}

		}

		if ( $args_index ) {
			/*
			 * If $args are used in the $parmeters array
			 * Then remove every one of the ${context} variables.
			 */
			foreach ( array_keys( $parameters[ $args_index ] ) as $key_name ) {
				if ( '$' == $key_name[0] ) {
					unset( $parameters[ $args_index ][ $key_name ] );
				}
			}

		}

		/**
		 * return the built set of parameters.
		 */
		return $parameters;

	}

}

/**
 * Class WP_Registry
 *
 * Simple class to implement a registry for values like 'field_types' and 'feature_types'.
 *
 */
final class WP_Registry {

	/**
	 * @var string
	 */
	var $registry_name;

	/**
	 * @var array
	 */
	private $_entries = array();

	/**
	 * Instantiate a new registry.
	 *
	 * @param string $registry_name Name of the registry, for reference.
	 */
	function __construct( $registry_name ) {

		$this->registry_name = $registry_name;

	}

	/**
	 *
	 * Register an named entry and it's $data to this
	 *
	 * @param string $name Name of the Entry to Register
	 * @param mixed $data Arguments to register. This can be an array or even a string, such as a class name.
	 *
	 * @return int The index of the entry in the registry.
	 */
	function register_entry( $name, $data ) {

		$index = count( $this->_entries );

		$this->_entries[ $name ] = $data;

		return $index;

	}

	/**
	 * Get the $data for a named Entry from $this Registry.
	 *
	 * @param string $name Name of the Entry for which to get its $data from the Registry.
	 *
	 * @return mixed The $data for this Registry's named Entry, or null if no such named Entry found.
	 */
	function get_entry( $name ) {

		return isset( $this->_entries[ $name ] ) ? $this->_entries[ $name ] : null;

	}

	/**
	 * Test to see if $this Registry has the specified named Entry
	 *
	 * @param string $name Name of the Entry for which to test to see if it exists in the Registry.
	 *
	 * @return bool True if the named Entry exists in the Registry, false if not.
	 */
	function entry_exists( $name ) {

		return $name && is_string( $name ) && isset( $this->_entries[ $name ] );

	}

}
