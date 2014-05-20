<?php
/**
 * Class WP_Storage_Base
 */
abstract class WP_Storage_Base extends WP_Metadata_Base {

	/**
	 *
	 */
	const STORAGE_TYPE = 'unspecified';

	/**
	 *
	 */
	const OBJECT_ID = 'ID';

	/**
	 * @var WP_Post|WP_User|object
	 */
	var $object;

	/**
	 * @var WP_Field_Base
	 */
	var $owner;

	/**
	 * $storage_arg names that should not get a prefix.
	 *
	 * Intended to be used by subclasses.
	 *
	 * @return array
	 */
	static function NO_PREFIX() {

		return array(
			'owner',
			'object',
		);

	}

	/**
	 * @param object|WP_Metadata_Base $owner
	 * @param array $storage_args
	 */
	function __construct( $owner, $storage_args = array() ) {

		$this->owner = $owner;

		parent::__construct( $storage_args );

	}

	/**
	 * @return mixed $value
	 */
	function get_value() {

		return null;

	}

	/**
	 * @param null|mixed $value
	 */
	function update_value( $value = null ) {

	}

	/**
	 * Name used for owner key.
	 *
	 * Most common example of a owner key would be a meta key.
	 *
	 * @return string
	 */
	function storage_key() {

		return '_' . $this->owner->storage_key();

	}

	/**
	 * @return int
	 */
	function object_id() {

		if ( is_object( $this->object ) && property_exists( $this->object, self::OBJECT_ID ) ) {
			return $this->object->{self::OBJECT_ID};
		} else {
			return null;
		}

	}

	/**
	 * @param $object_id
	 */
	function set_object_id( $object_id ) {

		if ( is_object( $this->object ) && property_exists( $this->object, self::OBJECT_ID ) ) {
			$this->object->{self::OBJECT_ID} = $object_id;
		}

	}

	/**
	 *
	 * @param $field_name
	 *
	 * @return bool
	 */
	function has_field( $field_name ) {

		return true;

	}

}
