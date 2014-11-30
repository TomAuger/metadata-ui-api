<?php


class WP_Data extends WP_Object {

	/**
	 * @var WP_Item
	 */
	var $item;

	/**
	 * @return WP_Item
	 */
	function as_item() {

		return $this->item;

	}
}
