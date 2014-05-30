<?php
/**
 * Class WP_Object_Type
 */
final class WP_Object_Type {

	/**
	 * @var bool
	 */
	var $class = false;

	/**
	 * @var bool
	 */
	var $subtype = false;

	/**
	 * @var array
	 */
	protected static $_object_type_classes = array(
		'post' => array(),
		'user' => array(),
		'comment' => array(),
		//'option' => array(),
		//'site-option' => array(),)
	);

	/**
	 * @param bool|string|array|object $object_type
	 */
	function __construct( $object_type = false ) {

		if ( $object_type ) {

			$this->assign( $object_type );

		}

	}

	/**
	 * Register object type
	 *
	 * @param $class
	 * @param $class_args
	 *
	 * @return bool Whether the object type $class was registered
	 */
	public static function register_class( $class, $class_args = array() ) {

		if ( ! isset( self::$_object_type_classes[ $class ] ) ) {
			self::$_object_type_classes[ $class ] = $class_args;

			return true;
		}

		return false;

	}

	/**
	 * Validates and assigns a value to this Object Type
	 *
	 * @example:
	 *
	 *     $this->assign(
	 *
	 * @param bool|string|array|WP_Object_Type $object_type
	 */
	function assign( $object_type = false ) {

		if ( empty( $object_type ) ) {
			global $post;

			$object_type = wp_get_post_object_type( $post->post_type );
		}

		if ( is_a( $object_type, __CLASS__ ) ) {
			$this->class = $object_type->class;
			$this->subtype = $class->subtype;
		}
		else {
			if ( is_string( $object_type ) ) {
				if ( false === strpos( $object_type, ':' ) ) {
					$this->class = 'post';
					$this->subtype = $object_type;
				}
				else {
					list( $this->class, $this->subtype ) = explode( ':', $object_type );
				}
			}
			else {
				if ( is_array( $object_type ) ) {
					$object_type = (object) $object_type;
				}

				$this->class = property_exists( $object_type, 'class' ) ? $object_type->class : false;
				$this->subtype = property_exists( $object_type, 'subtype' ) ? $object_type->subtype : false;
			}

			$this->class = sanitize_key( $this->class );

			if ( $this->subtype ) {
				$this->subtype = sanitize_key( $this->subtype );
			}
		}

		if ( empty( $this->subtype ) ) {
			$this->subtype = 'any';
		}

	}

	/**
	 * Get the most specific type available
	 *
	 * @return string
	 */
	function unqualified_type() {

		return 'any' == $this->subtype ? $this->class : $this->subtype;

	}

	/**
	 * Check if the current object type is valid.
	 *
	 * @return bool
	 */
	public function is_valid() {

		return ! empty( $this->class );

	}

	/**
	 * Check if the current object type is equivalent to the one passed in.
	 *
	 * @param WP_Object_Type|string $that
	 *
	 * @return bool
	 */
	public function is_equivalent( $that ) {

		if ( ! is_a( $that, __CLASS__ ) ) {
			$that = new self( $that );
		}

		return $this == $that;

	}

	/**
	 * @return string
	 */
	function __toString() {

		return "{$this->class}:{$this->subtype}";

	}

}
