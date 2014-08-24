<?php
/**
 * Class WP_Html_Element
 */
class WP_Html_Element extends WP_Metadata_Base {

	/**
	 *
	 */
	const VOID_ELEMENTS = 'area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr';

	/**
	 * @var string
	 */
	var $tag_name;

	/**
	 * @var string
	 */
	var $value;

	/**
	 * @var array
	 */
	protected $_attributes;


	/**
	 * @return array
	 */
	static function CLASS_VARS() {
		return array(
			'parameters' => array(
				'$html_tag',
				'$value',
				null,
			)
    );
	}

  /**
 	 * @return array
 	 */
  static function PROPERTIES() {

    return array(
      '_attributes' => array( 'type' => 'string[]', 'prefix' => false ),
    );

  }

	/**
	 * Factory method for WP_Html_Element
	 *
	 * @param string $tag_name
	 * @param array $attributes
	 * @param null|callable|string $value
	 *
	 * @return self
	 */
	static function make_new( $tag_name, $attributes = array(), $value = null ) {

		return new self( $tag_name, $attributes, $value );

	}

	/**
	 * @param string $tag_name
	 * @param array $attributes
	 * @param null|callable|string $value
	 */
	function __construct( $tag_name, $attributes = array(), $value = null ) {

		$this->reset_element( $tag_name, $attributes, $value );

		parent::__construct( $attributes );

	}

	/**
	 * @param string $tag_name
	 * @param array $attributes
	 * @param null|callable|string $value
	 */
	function reset_element( $tag_name, $attributes = array(), $value = null ) {

		$this->tag_name = $tag_name;
		if ( is_null( $attributes ) ) {
			$attributes = array();
		}
		$this->_attributes = wp_parse_args( $attributes );
		$this->value = $value;

	}

	/**
	 * @return bool
	 */
	function is_void_element() {

		return preg_match( '#^(' . self::VOID_ELEMENTS . ')$#i', $this->tag_name ) ? true : false;

	}

	/**
	 * @return string
	 */
	function get_html() {

		$html = "<{$this->tag_name} " . $this->get_attributes_html() . '>';

		if ( ! $this->is_void_element() ) {
			$value = is_callable( $this->value ) ? call_user_func( $this->value, $this ) : $this->value;

			$html .= "{$value}</{$this->tag_name}>";
		}

		return $html;

	}

	/**
	 * @return array
	 */
	function get_attributes_html() {

		$valid_attributes = WP_Metadata::get_html_attributes( $this->tag_name );
		$attributes = array_filter( $this->attributes() );

		$html = array();

		if ( isset( $valid_attributes['value'] ) ) {
			$attributes['value'] = esc_attr( $this->value );
		}

		foreach ( $attributes as $name => $value ) {
			if ( false !== $value && isset( $valid_attributes[ $name ] ) ) {
				$html[] = "{$name}=\"{$value}\"";
			}
		}

		return implode( ' ', $html );

	}

	/**
	 * @return array
	 */
	function attributes() {

		return $this->_attributes;

	}

	/**
	 * @param $attribute_name
	 *
	 * @return mixed
	 */
	function get_attribute_value( $attribute_name ) {

		$attributes = $this->attributes();

		return ! empty( $attributes[ $attribute_name ] ) ? trim( $attributes[ $attribute_name ] ) : false;

	}

	/**
	 * @param string $attribute_name
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	function set_attribute_value( $attribute_name, $value ) {

		$this->_attributes[ $attribute_name ] = $value;

	}

	/**
	 * @param string $attribute_name
	 * @param mixed $value
	 *
	 */
	function append_attribute_value( $attribute_name, $value ) {

		if ( isset( $this->_attributes[ $attribute_name ] ) ) {

			$value = trim( "{$this->_attributes[ $attribute_name ]} {$value}" );

		}

		$this->_attributes[ $attribute_name ] = $value;

	}

	/**
	 * @param $attribute_name
	 *
	 * @return mixed
	 */
	function get_attribute_html( $attribute_name ) {

		$value = $this->get_attribute_value( $attribute_name );

		/**
		 * @todo How best to escape the attribute_name; esc_attr() or other?
		 */
		return $value ? esc_attr( $attribute_name ) . '="' . esc_attr( $value ) . '"' : false;

	}

	/**
	 * Shortcut to get 'id'
	 *
	 * @return string
	 */
	function get_id() {

		return $this->get_attribute_value( 'id' );

	}

	/**
	 * Shortcut to get 'name'
	 *
	 * @return string
	 */
	function get_name() {

		return $this->get_attribute_value( 'name' );

	}

	/**
	 * Shortcut to get 'class'
	 *
	 * @return string
	 */
	function get_class() {

		return $this->get_attribute_value( 'class' );

	}

	/**
	 * Shortcut to set 'id'
	 *
	 * @param $value
	 */
	function set_id( $value ) {

		$this->set_attribute_value( 'id', $value );

	}

	/**
	 * Shortcut to set 'name'
	 *
	 * @param $value
	 */
	function set_name( $value ) {

		$this->set_attribute_value( 'name', $value );

	}

	/**
	 * Shortcut to set 'class'
	 *
	 * @param $value
	 */
	function set_class( $value ) {

		$this->get_attribute_value( 'class', $value );

	}

	/**
	 * Shortcut to append 'class' value
	 *
	 * @param $value
	 */
	function append_class( $value ) {

		$this->append_attribute_value( 'class', $value );

	}

}
