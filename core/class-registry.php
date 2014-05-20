<?php
/**
 * Class WP_Registry
 */
class WP_Registry extends WP_Metadata_Base {

	/**
	 * @var array
	 */
	private $_entries = array();

	/**
	 * @param string $name
	 * @param array $args
	 *
	 * @return int
	 */
	function register_entry( $name, $args ) {

		$index = count( $this->_entries );

		$this->_entries[ $name ] = $args;

		return $index;

	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	function get_entry( $name ) {

		return isset( $this->_entries[ $name ] ) ? $this->_entries[ $name ] : null;

	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	function entry_exists( $name ) {

		return $name && is_string( $name ) && isset( $this->_entries[ $name ] );

	}

}
