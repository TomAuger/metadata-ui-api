<?php
/**
 * Class WP_Field_Feature_Base
 */
abstract class WP_Field_Feature_Base extends WP_View_Base {

	/**
	 *
	 */
	const HTML_TYPE = false;

	/**
	 * @var string
	 */
	var $feature_type;

	/**
	 * @var WP_Field_Base
	 */
	var $field;

	/**
	 * @var WP_Field_View_Base
	 */
	var $view;


  /**
 	 * @return array
 	 */
  static function PROPERTIES() {

    return array(
      'field' => array( 'type' => 'WP_Field_Base', 'auto_create' => false ),
    );

  }

	/**
  * Defines the PARAMETERS for the static class factory method 'make_new'.
  *
  * @return array
  */
 static function PARAMETERS() {

   return array(
     '$value',
     '$parent',
     '$args',
   );

 }


 /**
  * Returns a new instance of a Field Feature object.
  *
  * @param string $feature_type
  * @param WP_Field_View_Base $view
  * @param array $feature_args
	*
	* @return null|WP_Field_Feature_Base
	*/
 static function make_new( $feature_type, $view, $feature_args = array() ) {

   if ( $feature_type_class = WP_Metadata::get_feature_type_class( $feature_type ) ) {

	   $feature_args['feature_type'] = $feature_type;

     $feature = new $feature_type_class( $view, $feature_args );

   } else {

     $feature = null;

   }

		return $feature;

	}

	/**
	 * @param WP_Field_View_Base $view
	 * @param array $feature_args
	 */
	function __construct( $view, $feature_args = array() ) {

		$this->field = $view->field;
		$this->view = $view;

		parent::__construct( $feature_args );

		$this->owner = $this->field;

	}

	/**
	 * @return bool|string
	 */
	function element_id() {

		return str_replace( '_', '-', $this->field->view->get_element_id() ) . "-field-{$this->feature_type}";

	}

	/**
	 * @return bool|string
	 */
	function element_class() {

		return "field-feature field-{$this->feature_type}";

	}

	/**
	 * @return bool|string
	 */
	function element_name() {

		return 'wp_metadata_forms[' . $this->field->form_element_name() . '][' . $this->field->view->get_element_name() . ']';

	}

	/**
	 * @return bool|string
	 */
	function element_type() {

		return $this->field->constant( 'HTML_TYPE' );

	}

	/**
	 * @return string
	 */
	function get_feature_html() {

		$this->get_html();

	}


}
