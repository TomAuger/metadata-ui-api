<?php
/**
 * Class WP_Meta_Storage
 */
class WP_Meta_Storage extends WP_Storage_Base {

	/**
	 *
	 */
	const STORAGE_TYPE = 'meta';

	/**
	 *
	 */
	const PREFIX = 'meta_';

	/**
	 * @var bool|string - Meta type such as 'post', 'user' and 'comment' (in future, other.)
	 */
	var $meta_type = false;


	/**
	 * @param array $storage_args
	 * @return array
	 */
	function pre_assign_args( $storage_args ) {

		if ( empty( $storage_args['meta_type'] ) ) {

			$storage_args['meta_type'] = 'post';

		}

		return $storage_args;

	}

	/**
	 * @return mixed
	 */
	function get_value() {

		return get_metadata( $this->meta_type, $this->object_id(), $this->storage_key(), true );

	}

	/**
	 * @param null|mixed $value
	 */
	function update_value( $value = null ) {

		update_metadata( $this->meta_type, $this->object_id(), $this->storage_key(), esc_sql( $value ) );

	}

}
