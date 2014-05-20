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
	 * @return mixed $value
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
