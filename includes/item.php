<?php


class WP_Item extends WP_Object {

	/**
	 * @var WP_Data
	 */
	var $data;

	/**
	 * @var WP_Tags
	 */
	var $tags;

	function __construct( $item_args = array() ) {

		if ( empty( $item_args['data'] ) ) {

			if ( ! ( $item_args['data'] = WP_Metadata::make_new( get_class( $this ) . '_Data' ) ) ) {

				$item_args['data'] = new WP_Item_Data();

			}
		}
		$item_args['data']->item = $this;

		if ( empty( $item_args['tags'] ) ) {

			if ( ! ( $item_args['tags'] = WP_Metadata::make_new( get_class( $this ) . '_Tags' ) ) ) {

				$item_args['tags'] = new WP_Item_Tags();

			}

		}
		$item_args['tags']->item = $this;

		parent::__construct( $item_args );


	}

	/**
	 * @param string $property_name
	 *
	 * @return mixed|null
	 */
	function get_property( $property_name ) {

		return $this->data->get_property( $property_name );

	}

	/**
	 * @param string $property_name
	 * @param mixed $value
	 */
	function set_property( $property_name, $value ) {

		$this->data->set_property( $property_name, $value );

	}


	/**
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return mixed
	 */
	function __call( $method_name, $args = array() ) {
		$value = null;

		if ( $this->tags->has_method( $method_name ) ) {

			$value = $this->tags->call_method( $method_name, $args );

		} else if ( $this->data->has_method( $method_name ) ) {

			echo $value = $this->data->call_method( $method_name, $args );

		} else {

			$message = __( 'ERROR: No method %s exists for class %s or in its data or its tags.', 'wp-metadata' );
			trigger_error( sprintf( $message, $method_name, get_class( $this ) ) );

		}

		return $value;

	}

}

class WP_Item_Data extends WP_Data {

}

class WP_Item_Tags extends WP_Tags {

}



