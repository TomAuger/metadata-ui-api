<?php
/**
 * Class WP_Object_Type
 */
final class WP_Object_Type {

	/**
	 * @var bool
	 */
	var $object_type = false;

	/**
	 * @var bool
	 */
	var $subtype = false;

	/**
	 * @var array
	 */
	protected static $_core_object_types = array(
		'post' => array( 'has_subtype' => true ),
		'user' => array( 'has_subtype' => false ),
		'comment' => array( 'has_subtype' => false ), // @todo Set to true when comment types get core support
	);

	/**
	 * @param bool|string|array|object $object_type
	 */
	function __construct( $object_type = false ) {

		if ( $object_type ) {
			$this->assign_type( $object_type );
		}

	}

	/**
	 * Validated and assigns a value to this Object Type
	 *
	 * @example:
	 *
	 *     $this->assign(
	 *
	 * @param bool|string|array|WP_Object_Type $object_type
	 */
	function assign_type( $object_type = false ) {

		if ( empty( $object_type ) ) {
			global $post;

			$object_type = wp_get_post_object_type( $post->post_type );
		}

		if ( is_a( $object_type, __CLASS__ ) ) {
			$this->object_type = $object_type->object_type;
			$this->subtype = $object_type->subtype;
		}
		else {
			if ( is_string( $object_type ) ) {
				if ( isset( self::$_core_object_types[ $object_type ] ) && !self::$_core_object_types[ $object_type ][ 'has_subtype' ] ) {
					$this->object_type = $object_type;
					$this->subtype = false;
				}
				elseif ( false === strpos( $object_type, ':' ) ) {
					$this->object_type = 'post';
					$this->subtype = $object_type;
				}
				else {
					list( $this->object_type, $this->subtype ) = explode( ':', $object_type );
				}
			}
			else {
				if ( is_array( $object_type ) ) {
					$object_type = (object) $object_type;
				}

				$this->object_type = property_exists( $object_type, 'object_type' ) ? $object_type->object_type : false;
				$this->subtype = property_exists( $object_type, 'subtype' ) ? $object_type->subtype : false;
			}

			$this->object_type = sanitize_key( $this->object_type );

			if ( $this->subtype ) {
				$this->subtype = sanitize_key( $this->subtype );
			}
		}

	}

	/**
	 * Get the most specific type available
	 *
	 * @return string
	 */
	function unqualified_type() {

		return empty( $this->subtype ) ? $this->object_type : $this->subtype;

	}

	/**
	 * Check if the current object type is valid.
	 *
	 * @return bool
	 */
	public function is_valid() {

		return !empty( $this->type );

	}

	/**
	 * Check if the current object type is equivalent to the one passed in.
	 *
	 * @param WP_Object_Type $that
	 *
	 * @return bool
	 */
	public function is_equivalent( $that ) {

		if ( !is_a( $that, __CLASS__ ) ) {
			$object_type = new self( $that ); // @todo Unused variable
		}

		return $this == $that;

	}

	/**
	 * @return string
	 */
	function __toString() {

		return "{$this->object_type}:{$this->subtype}";

	}

}