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
	 * @var bool
	 */
	private $_attributes_parsed;

  /**
 	 * @return array
 	 */
  static function PROPERTIES() {

    return array(
      '_attributes' => array( 'type' => 'string[]', 'prefix' => false ),
    );

  }

	/**
	 * Defines the PARAMETERS for the static class factory method 'make_new'.
	 *
	 * @return array
	 */
	static function PARAMETERS() {

	 return array(
	   '$html_tag',
		 '$value',
	    null,
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
		$this->_attributes_parsed = false;

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
			if ( $value && isset( $valid_attributes[ $name ] ) ) {
				$html[] = "{$name}=\"{$value}\"";
			}
		}

		return implode( ' ', $html );

	}

	/**
	 * @return array
	 */
	function attributes() {

		if ( !$this->_attributes_parsed ) {
			$attributes = WP_Metadata::get_html_attributes( $this->tag_name );

			foreach ( $this->_attributes as $name => $value ) {
				$attributes[ sanitize_key( $name ) ] = esc_attr( $value );
			}

			$this->_attributes = $attributes;
			$this->_attributes_parsed = true;
		}

		return $this->_attributes;

	}

	/**
	 * @param $attribute_name
	 *
	 * @return mixed
	 */
	function get_attribute_value( $attribute_name ) {

		$attributes = $this->attributes();

		return !empty( $attributes[ $attribute_name ] ) ? $attributes[ $attribute_name ] : false;

	}

	/**
	 * @param $attribute_name
	 *
	 * @return mixed
	 */
	function set_attribute_value( $attribute_name, $value ) {

		if ( !$this->_attributes_parsed ) {
			$this->attributes();
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

}
