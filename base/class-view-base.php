<?php
/**
 * Class WP_View_Base
 *
 * @TODO Refactor so this class can handle an arbitrary list of properties, not just 'wrapper' and 'element'
 *
 * @method null|int element_id()
 * @method null|string element_name()
 * @method null|string element_class()
 *
 */
class WP_View_Base extends WP_Metadata_Base {

	/**
	 * @var WP_Html_Element
	 */
	var $wrapper = null;

	/**
	 * @var WP_Html_Element
	 */
	var $element = null;

	/**
	 * @var WP_Metadata_Base
	 */
	var $owner;

	/**
	 * @return array
	 */
	static function TRANSFORMS() {

		return array(
      /**
       * @example Allow "wrapper:{$property}" as shortcut to "wrapper:html:class".
       */
			'^wrapper:html:(.+)$' => 'wrapper:$1',
			'^element:html:(.+)$' => 'element:$1',
		);
	}

	/**
	* @return array
	*/
	static function PROPERTIES() {

		return array(
		  'wrapper' => array( 'type' => 'WP_Html_Element', 'html_tag' => 'div' ),
		  'element' => array( 'type' => 'WP_Html_Element', 'html_tag' => 'div' ),
		);

	}

	/**
	 * @return string
	 */
	function get_html() {

		if ( is_object( $wrapper = $this->wrapper ) && method_exists( $wrapper, 'get_html' ) ) {

			$wrapper->value = $this->get_wrapper_value();

			$html = $wrapper->get_html();

		} else {

			$html = null;

		}

		return $html;

	}

	/**
	 * This typically gets overridden in a child class.
	 *
	 * Wrapper Value =>  <div class="wrapper">{$value}</div>
	 *
	 * @return string
	 */
	function get_wrapper_value() {

		return null;
	}


	/**
	 * Return the HTML tag to be used by this class.
	 * @return array
	 */
	function get_element_tag() {

		return $this->owner->get_annotation_of( 'html_tag', 'element' );

	}

	/**
	 * @return bool|string
	 */
	function get_element_id() {

		return $this->get_attribute_value_of( 'id', 'element' );

	}

	/**
	 * @return bool|string
	 */
	function get_element_class() {

		return $this->get_attribute_value_of( 'class', 'element' );

	}

	/**
	 * @return bool|string
	 */
	function get_element_name() {

		return $this->get_attribute_value_of( 'name', 'element' );

	}

	/**
	 * Return the HTML tag to be used by this class.
	 * @return array
	 */
	function get_wrapper_tag() {

		return $this->owner->get_annotation_of( 'html_tag', 'wrapper' );

	}

//	/**
//	 * @return array
//	 */
//	function wrapper_name() {
//
//		return false;
//
//	}

	/**
	 * @return bool|string
	 */
	function get_wrapper_id() {

		return $this->get_attribute_value_of( 'id', 'wrapper' );

	}

	/**
	 * @return bool|string
	 */
	function get_wrapper_class() {

		return $this->get_attribute_value_of( 'class', 'wrapper' );

	}

	/**
	 * @return bool|string
	 */
	function get_wrapper_name() {

		return $this->get_attribute_value_of( 'name', 'wrapper' );

	}

	/**
	 * @param array $feature_args
	 *
	 * @return array
	 */
	function initialize( $feature_args ) {

		$this->get_attribute_value_of( 'name', 'element', true );
		$this->get_attribute_value_of( 'id', 'element', true );

		$this->get_attribute_value_of( 'name', 'wrapper', true );
		$this->get_attribute_value_of( 'id', 'wrapper', true );

		$class = $this->get_attribute_value_of( 'class', 'element' );

		if ( $class != ( $more_class = $this->element_class() ) ) {

			$class = "{$class} {$more_class}";

		}

		$this->set_attribute_value_of( 'class', $class, 'element' );

		$class = explode( ' ', trim( $class ) );
		foreach( array_keys( $class ) as $index ) {
			$class[ $index ] .= '-wrapper';
		}
		$class = implode( ' ', $class );

		$this->set_attribute_value_of( 'class', $class, 'wrapper' );

	}

	/**
	 * Get an attributes of a WP_Html_Element property (and of any object with a 'get_attribute()' method.
	 *
	 * @param string $attribute_name
	 * @param string $property_name
	 * @param bool $set_value
	 *
	 * @return mixed
	 */
	function get_attribute_value_of( $attribute_name, $property_name, $set_value = false ) {

		$attribute_value = null;

		/**
		 * @var WP_Html_Element $element
		 */
		if ( isset( $this->{$property_name} ) && is_object( $element = $this->{$property_name} ) ) {

			if ( method_exists( $element, 'get_attribute_value' ) ) {

				if ( false === ( $attribute_value = $element->get_attribute_value( $attribute_name ) && $set_value ) ) {

					if ( ! is_null( $attribute_value = $this->get_defined_attribute_value_of( $attribute_name, $property_name ) ) ) {

						$this->set_attribute_value_of( $attribute_name, $attribute_value, $property_name );

					}

				}

			}

		}

		return $attribute_value;

	}

	/**
	 *
	 * A "defined" attribute value is gotten from a method named "{$property_name}_{$attribute_name}()"
	 *
	 * This will be handled in __call() if there is no defined method.
	 *
	 * @param $attribute_name
	 * @param $property_name
	 *
	 * @return mixed|null
	 */
	function get_defined_attribute_value_of( $attribute_name, $property_name ) {

		return call_user_func( array( $this, "{$property_name}_{$attribute_name}" ) );

	}

	/**
	 * Set an attributes of a WP_Html_Element property (and of any object with a 'set_attribute_value()' method.
	 *
	 * @param string $attribute_name
	 * @param string $property_name
	 * @param string $attribute_value
	 */
	function set_attribute_value_of( $attribute_name, $attribute_value, $property_name ) {

		if ( isset( $this->{$property_name} ) && is_object( $object = $this->{$property_name} ) ) {

			if ( method_exists( $object, 'set_attribute_value' ) ) {

				$object->set_attribute_value( $attribute_name, $attribute_value );

			}

		}

	}

	function __call( $method_name, $args ) {

		$value = null;

		if ( preg_match( "#^get_(element|wrapper)_(id|name|class)$#", $method_name, $matches ) ) {

			$value = $this->_get_element_attribute_value( $matches[1], $matches[2] );

		} else if ( preg_match( "#^(wrapper)_(id|name|class)$#", $method_name, $matches ) ) {

				if ( ! method_exists( $this, $method_name = "{$matches[1]}_{$matches[2]}" ) ) {

					if ( 'class' == $matches[2] ) {
						/*

						 * @todo Figure out what to add here.
						 */
						$value = $this->element->get_attribute_value( $matches[2] );

					} else /** if ( 'class' != $matches[2] ) */{

						$separator = 'name' == $matches[2] ? '_' : '-';

						$value = $this->element->get_attribute_value( $matches[2] ) . $separator . $matches[1];

					}

				}

		} else {

			$value = parent::__call( $method_name, $args );

		}

		return $value;

	}

	private function _get_element_attribute_value( $element_name, $attribute_name ) {

		$value = $this->get_attribute_value_of( $attribute_name, $element_name );

		if ( empty( $value ) && method_exists( $this, $method_name = "{$element_name}_{$attribute_name}" ) ) {

			if ( $value = call_user_func( array( $this, $method_name ) ) ) {

				$this->set_attribute_value_of( $attribute_name, $value, $element_name );

			}

		}

		return $value;

	}
}
