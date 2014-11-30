<?php

/**
 * Class WP_Tags
 */
class WP_Tags extends WP_Object {

	/**
	 * @var WP_Item
	 */
	var $item;

	/**
	 * @var array
	 */
	private $_real_method = array();


	/**
	 * Get data
	 *
	 * @return WP_Data
	 */
	function data() {

		return $this->item->data;

	}

	/**
	 * @return string
	 */
	function get_html() {
		return '<div>' . __( 'HTML not specified.', 'wp-metadata' ) . '</div>';
	}


	/**
	 * Magic method for getting inaccessible properties
	 * Examples:
	 *  $this->ID       Return ID
	 *  $this->the_ID   Output ID
	 *
	 * @param string $property_name
	 *
	 * @return mixed|null
	 */
	function __get( $property_name ) {

		$value = null;

		if ( $this->has_method( $property_name ) ) {

			$value = $this->call_method( $property_name );

		} else if ( $this->has_method( $get_method = "get_{$property_name}" ) ) {

			$value = $this->call_method( $get_method );

		} else {

			$value = $this->item->get_property( $property_name );

		}

		return $value;
	}

	/**
	 * Magic method for setting inaccessible properties
	 *
	 * @param string $property_name
	 * @param mixed $value
	 *
	 * @return void
	 */
	function __set( $property_name, $value ) {

		if ( $this->has_method( $method_name = "set_{$property_name}" ) ) {

			$this->call_method( $method_name, $value );

		} else {

			$this->item->set_property( $property_name, $value );

		}

	}

	/**
	 * @param string $method_name
	 *
	 * @return bool|object
	 */
	function _get_real_method( $method_name ) {

		if ( empty( $this->_real_method[ $method_name ] ) ) {

			$real_method = array( 'real_name' => false );
			$regex = '#^((the)[a-z_0-9]*?|((a-z_)[a-z_0-9]*?))?(_)?(html|link|url|attr|js|text|textarea)?$#';
			if ( preg_match( $regex, $method_name, $matches ) ) {

				$real_method[ 'prefix' ] = $matches[ 1 ];
				$real_method[ 'middle' ] = $matches[ 3 ];
				$real_method[ 'suffix' ] = ! empty( $matches[ 5 ] ) ? $matches[ 5 ] : '';

				$matches[ 0 ] = '';

				if ( 'the' == $matches[2] ) {
					$matches[ 1 ] = 'get';
					$matches[ 2 ] = '';
				}

				$test_method_name = implode( '', $matches );

				/**
				 * Real method has a hardcoded suffix
				 */
				if ( method_exists( $this, $test_method_name ) ) {

					$real_method[ 'real_name' ] = $test_method_name;

					/**
					 * Hardcoded suffix means no auto sanitize, 'we know what we are doing.'
					 */
					$real_method[ 'auto_sanitize' ] = false;

				} else if ( ! empty( $matches[ 6 ] ) ) {

					/**
					 * Remove trailing slash and suffix
					 */
					$matches[ 5 ] = $matches[ 6 ] = '';
					/**
					 * No hardcoded suffix means auto sanitize.
					 */
					$real_method[ 'auto_sanitize' ] = true;

					$test_method_name = implode( '', $matches );

					if ( method_exists( $this, $test_method_name ) ) {

						$real_method[ 'real_name' ] = $test_method_name;

					}

				}

			}

			$this->_real_method[ $method_name ] = (object)$real_method;
		}
		return $this->_real_method[ $method_name ];

	}

	/**
	 * @param string $method_name
	 * @return bool
	 */
	function has_method( $method_name ) {

		if ( ! ( $has_method = parent::has_method( $method_name ) ) ) {

			if ( $this->_get_real_method( $method_name )->real_name ) {

				$has_method = true;

			}

		}

		return $has_method;
	}

	/**
	 * Magic method for calling inaccessible methods
	 * Examples:
	 *  $this->date             Return original ISO 8601 date format from data
	 *  $this->get_date()       Return custom formatted date
	 *  $this->get_date_html()  Return custom formatted date HTML
	 *  $this->the_date()       Output custom formatted date
	 *  $this->the_date_html()  Output custom formatted date HTML
	 *
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return mixed|null
	 */
	function __call( $method_name, $args = array() ) {

		$value = null;

		if ( $this->has_method( $method_name ) ) {

			$real_method = $this->_get_real_method( $method_name );

			$value = $this->call_method( $real_method->real_name, $args );

			if ( $real_method->auto_sanitize ) {
				/**
				 * We found a method without a suffix to match the request method with suffix
				 * so we need to auto-sanitize the value.
				 */

				switch ( $real_method->suffix ) {

					case 'html':
					case 'link':
						$value = wp_kses_post( $value );
						break;

					case 'attr':
						$value = esc_attr( $value );
						break;

					case 'js':
						$value = esc_js( $value );
						break;

					case 'url':
						$value = esc_url( $value );
						break;

					case 'text':
					default:
						$value = esc_html( $value );
						break;

				}

			}
			if ( ! empty( $real_method->prefix ) ) {
				/*
				 * A non-empty prefix will be 'the_' so we should echo the value;
				 */
				echo $value;

			}


		} else if ( $this->item->has_method( $method_name ) ) {

			$value = $this->item->call_method( $method_name, $args );
		}

		return $value;
	}

}
