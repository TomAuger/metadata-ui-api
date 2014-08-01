<?php
/*
GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Example code structures from Custom Metadata (Automattic)
 *
 * @link https://github.com/Automattic/custom-metadata/blob/master/custom_metadata_examples.php
 */

/**
 * Register custom fields and forms
 *
 * This is the example code that you should use
 *
 * @return void
 */
function wpm_example_init_custom_fields() {

	/**
	 * Register post forms, these are groups and create meta box form fields
	 */

	// Document all general arguments / handling here
	$all_form_arguments = array(
		// Form Meta Box Title
		'label' => 'Meta Box Title'
	);

	// Add a new group
	// Post Type: wpm_example_test
	register_post_form( 'wpm_example_meta_box_1', 'wpm_example_test', array(
		'label' => 'Group with Multiple Fields'
	) );

	// Add a second group
	// Post Types: wpm_example_test, post
	register_post_form( 'wpm_example_meta_box_2', array( 'wpm_example_test', 'post' ), array(
		'label'       => 'Group for Post and Test',
		'description' => "Here's a group with a description!",
	) );

	/**
	 * Register post fields, these are examples of every field type and feature argument
	 */

	// Document all general arguments / handling here
	$all_field_arguments = array(
		// Form name
		'form'    => 'group_name', // (leave blank / unset for default location)

		// Field type: text, date, hidden, textarea, url, editor
		'type'    => '%s', // (leave blank / unset for text)

		// Field label
		'label'   => 'Field Label',
		// HTML attributes
		'html_%s' => '%s'
	);

	// adds a text field to the first group
	register_post_field( 'wpm_example_field_name_1', 'wpm_example_test', array(
		'form'           => 'wpm_example_meta_box_1', // the group name
		'type'           => 'text',
		'label'          => 'Text Field', // field label
		'description'    => 'This is field #1. It\'s a simple text field.', // description for the field
		'display_column' => true // show this field in the column listings
	) );

	// adds a text field to the 2nd group
	register_post_field( 'wpm_example_field_name_2', 'wpm_example_test', array(
		'form'                    => 'wpm_example_meta_box_1',
		'type'                    => 'text',
		// custom function to display the column results (see below)
		'label'                   => 'Text with Custom Callback',
		'display_column'          => 'My Column (with Custom Callback)',
		// show this field in the column listings
		'display_column_callback' => 'wpm_example_field_name2_callback',
	) );

	// adds a cloneable textarea field to the 1st group
	register_post_field( 'wpm_example_field_textarea_1', 'wpm_example_test', array(
		'form' => 'wpm_example_meta_box_1',
		'type' => 'textarea'
	) );

	// adds a readonly textarea field to the 1st group
	register_post_field( 'wpm_example_field_textarea_readonly_1', 'wpm_example_test', array(
		'form'          => 'wpm_example_meta_box_1',
		'type'          => 'textarea',
		'label'         => 'Read Only Text Area',
		'html_readonly' => true
	) );

	// adds a readonly text field to the 1st group
	register_post_field( 'wpm_example_field_text_readonly_1', 'wpm_example_test', array(
		'form'          => 'wpm_example_meta_box_1',
		'type'          => 'text',
		'label'         => 'Read Only Text Field',
		'html_readonly' => true,
	) );

	// adds a wysiwyg (full editor) field to the 2nd group for test cpt + posts
	register_post_field( 'wpm_example_field_wysiwyg_1', array( 'wpm_example_test', 'post' ), array(
		'form'  => 'wpm_example_meta_box_2',
		'type'  => 'editor',
		'label' => 'TinyMCE / Wysiwyg field',
	) );

	// adds a Date picker field to the 1st group
	register_post_field( 'wpm_example_field_date_picker_1', 'wpm_example_test', array(
		'form'  => 'wpm_example_meta_box_1',
		'type'  => 'date',
		'label' => 'Date picker field',
	) );

	// adds a Datetime picker field to the 1st group
	register_post_field( 'wpm_example_field_datetime_picker_1', 'wpm_example_test', array(
		'form'  => 'wpm_example_meta_box_1',
		'type'  => 'datetime',
		'label' => 'Datetime picker field',
	) );

	// adds a Time picker field to the 1st group
	register_post_field( 'wpm_example_field_time_picker_1', 'wpm_example_test', array(
		'form'  => 'wpm_example_meta_box_1',
		'type'  => 'time',
		'label' => 'Time picker field',
	) );

	// adds a colorpicker field to the 1st group
	register_post_field( 'wpm_example_field_color_picker_1', 'wpm_example_test', array(
		'form'  => 'wpm_example_meta_box_1',
		'type'  => 'colorpicker',
		'label' => 'Colorpicker field',
	) );

	// adds an upload field to the 1st group
	register_post_field( 'wpm_example_field_upload_1', 'wpm_example_test', array(
		'form'     => 'wpm_example_meta_box_1',
		'type'     => 'upload',
		'readonly' => true,
		'label'    => 'Upload field',
	) );

	// adds a checkbox field to the first group
	register_post_field( 'wpm_example_field_checkbox_1', 'wpm_example_test', array(
		'form'  => 'wpm_example_meta_box_1',
		'type'  => 'checkbox',
		'label' => 'Checkbox field',
	) );

	// adds a radio button field to the first group
	register_post_field( 'wpm_example_field_radio_1', 'wpm_example_test', array(
		'form'   => 'wpm_example_meta_box_1',
		'type'   => 'radio',
		'values' => array( // set possible value/options
						   'option1' => 'Option #1', // key => value pair (key is stored in DB)
						   'option2' => 'Option #2',
		),
		'label'  => 'Radio field',
	) );

	// adds a select box in the first group
	register_post_field( 'wpm_example_field_select_1', 'wpm_example_test', array(
		'form'   => 'wpm_example_meta_box_1',
		'type'   => 'select',
		'values' => array( // set possible value/options
						   'option1' => 'Option #1', // key => value pair (key is stored in DB)
						   'option2' => 'Option #2',
		),
		'label'  => 'Select field',
	) );

	// adds a multi-select field in the first group
	register_post_field( 'wpm_example_field_multi_select', 'wpm_example_test', array(
		'form'   => 'wpm_example_meta_box_1',
		'type'   => 'multi_select',
		'values' => array( // set possible value/options
						   'option1' => 'Option #1', // key => value pair (key is stored in DB)
						   'option2' => 'Option #2',
						   'option3' => 'Option #3',
						   'option4' => 'Option #4',
		),
		'label'  => 'Multi Select field',
	) );

	// adds a multi-select field with chosen in the first group
	// note: `select2` and `chosen` args do the exact same (add select2)
	// but for the purposes of testing, we're using chosen here
	register_post_field( 'wpm_example_field_multi_select_chosen', 'wpm_example_test', array(
		'form'   => 'wpm_example_meta_box_1',
		'type'   => 'multi_select',
		'values' => array( // set possible value/options
						   'option1' => 'Option #1', // key => value pair (key is stored in DB)
						   'option2' => 'Option #2',
						   'option3' => 'Option #3',
						   'option4' => 'Option #4',
		),
		'label'  => 'Multi Select field (with chosen)',
		'chosen' => true,
	) );

	// adds a select field with select2 in the first group
	register_post_field( 'wpm_example_field_select_select2', 'wpm_example_test', array(
		'form'    => 'wpm_example_meta_box_1',
		'type'    => 'select',
		'values'  => array( // set possible value/options
							'option1' => 'Option #1', // key => value pair (key is stored in DB)
							'option2' => 'Option #2',
							'option3' => 'Option #3',
							'option4' => 'Option #4',
		),
		'label'   => 'Select field (with select2)',
		'select2' => true,
	) );

	// adds a taxonomy checkbox field in the first group
	register_post_field( 'wpm_example_field_taxonomy_checkbox', 'wpm_example_test', array(
		'form'     => 'wpm_example_meta_box_1',
		'type'     => 'taxonomy_checkbox',
		'taxonomy' => 'category',
		'label'    => 'Category checkbox field',
	) );

	// adds a taxonomy select field in the first group
	register_post_field( 'wpm_example_field_taxonomy_select', 'wpm_example_test', array(
		'form'     => 'wpm_example_meta_box_1',
		'type'     => 'taxonomy_select',
		'taxonomy' => 'category',
		'label'    => 'Category select field',
	) );

	// adds a taxonomy multiselect field in the first group
	register_post_field( 'wpm_example_field_taxonomy_multi_select', 'wpm_example_test', array(
		'form'     => 'wpm_example_meta_box_1',
		'type'     => 'taxonomy_multi_select',
		'taxonomy' => 'category',
		'label'    => 'Category multiselect field',
	) );

	// adds a taxonomy multiselect w/ select2 field in the first group
	register_post_field( 'wpm_example_field_taxonomy_multi_select2', 'wpm_example_test', array(
		'form'     => 'wpm_example_meta_box_1',
		'type'     => 'taxonomy_multi_select',
		'taxonomy' => 'category',
		'label'    => 'Category multiselect w/ select2 field',
		'select2'  => true,
	) );

	// adds a number field in the first group (with no min/max)
	register_post_field( 'wpm_example_field_number', 'wpm_example_test', array(
		'form'  => 'wpm_example_meta_box_1',
		'type'  => 'number',
		'label' => 'Number field',
	) );

	// adds a number field in the first group (with min/max)
	register_post_field( 'wpm_example_field_number_with_min_max', 'wpm_example_test', array(
		'form'     => 'wpm_example_meta_box_1',
		'type'     => 'number',
		'min'      => '-3',
		'max'      => '25',
		'multiple' => true,
		'label'    => 'Number field (with min/max + cloneable)',
	) );

	// adds an email field in the first group
	register_post_field( 'wpm_example_field_email', 'wpm_example_test', array(
		'form'  => 'wpm_example_meta_box_1',
		'type'  => 'email',
		'label' => 'Email field',
	) );

	// adds a url field in the first group
	register_post_field( 'wpm_example_field_link', 'wpm_example_test', array(
		'form'  => 'wpm_example_meta_box_1',
		'type'  => 'url',
		'label' => 'URL field',
	) );

	// adds a telephone field in the first group (with default value)
	register_post_field( 'wpm_example_field_telephone', 'wpm_example_test', array(
		'form'          => 'wpm_example_meta_box_1',
		'type'          => 'tel',
		'label'         => 'Telephone field',
		'default_value' => '123-4567'
	) );

	// adds a text field with a default value
	register_post_field( 'wpm_example_field_text_default', 'wpm_example_test', array(
		'form'          => 'wpm_example_meta_box_1',
		'type'          => 'text',
		'label'         => 'Text field with default value',
		'default_value' => 'lorem ipsum'
	) );

	// adds a text field with placeholder
	register_post_field( 'wpm_example_field_textarea_placeholder', 'wpm_example_test', array(
		'form'        => 'wpm_example_meta_box_1',
		'type'        => 'textarea',
		'label'       => 'Textarea field with placeholder',
		'placeholder' => 'some placeholder text',
	) );

	// adds a password field with placeholder
	register_post_field( 'wpm_example_field_password_placeholder', 'wpm_example_test', array(
		'form'        => 'wpm_example_meta_box_1',
		'type'        => 'password',
		'label'       => 'Password field with placeholder',
		'placeholder' => 'some placeholder text',
	) );

	// adds a number field with placeholder
	register_post_field( 'wpm_example_field_number_placeholder', 'wpm_example_test', array(
		'form'        => 'wpm_example_meta_box_1',
		'type'        => 'number',
		'label'       => 'Number field with placeholder',
		'placeholder' => 'some placeholder text',
	) );

	// adds an email field with placeholder
	register_post_field( 'wpm_example_field_email_placeholder', 'wpm_example_test', array(
		'form'        => 'wpm_example_meta_box_1',
		'type'        => 'email',
		'label'       => 'Email field with placeholder',
		'placeholder' => 'some placeholder text',
	) );

	// adds a url field with placeholder
	register_post_field( 'wpm_example_field_link_placeholder', 'wpm_example_test', array(
		'form'        => 'wpm_example_meta_box_1',
		'type'        => 'url',
		'label'       => 'URL field with placeholder',
		'placeholder' => 'some placeholder text',
	) );

	// adds an telephone field with placeholder
	register_post_field( 'wpm_example_field_telephone_placeholder', 'wpm_example_test', array(
		'form'        => 'wpm_example_meta_box_1',
		'type'        => 'tel',
		'label'       => 'Telephone field with placeholder',
		'placeholder' => 'some placeholder text',
	) );

	// adds an upload field with placeholder
	register_post_field( 'wpm_example_field_upload_placeholder', 'wpm_example_test', array(
		'form'        => 'wpm_example_meta_box_1',
		'type'        => 'upload',
		'label'       => 'Upload field with placeholder',
		'placeholder' => 'some placeholder text',
	) );

	// adds an Date picker field with placeholder
	register_post_field( 'wpm_example_field_date_picker_placeholder', 'wpm_example_test', array(
		'form'        => 'wpm_example_meta_box_1',
		'type'        => 'date',
		'label'       => 'Date picker field with placeholder',
		'placeholder' => 'some placeholder text',
	) );

	// adds a Datetime picker field with placeholder
	register_post_field( 'wpm_example_field_datetime_picker_placeholder', 'wpm_example_test', array(
		'form'        => 'wpm_example_meta_box_1',
		'type'        => 'datetime',
		'label'       => 'Datetime picker field with placeholder',
		'placeholder' => 'some placeholder text',
	) );

	// adds a Time picker field with placeholder
	register_post_field( 'wpm_example_field_time_picker_placeholder', 'wpm_example_test', array(
		'form'        => 'wpm_example_meta_box_1',
		'type'        => 'time',
		'label'       => 'Time picker field with placeholder',
		'placeholder' => 'some placeholder text',
	) );

	// adds a field to posts only
	register_post_field( 'wpm_example_field_name_2', 'post', array(
		'form'  => 'wpm_example_meta_box_2',
		'label' => 'Text field',
	) );

	// adds a field with a custom display callback (see below)
	register_post_field( 'wpm_example_fieldCustomHidden1', 'wpm_example_test', array(
		'form'             => 'wpm_example_meta_box_1',
		'display_callback' => 'wpm_example_field_custom_hidden_1_callback', // this function is defined below
		'label'            => 'Hidden field',
	) );

	// field with capabilities limited
	register_post_field( 'wpm_example_cap-limited-field', 'wpm_example_test', array(
		'label'        => 'Cap Limited Field (edit_posts)',
		'required_cap' => 'edit_posts' // limit to users who can edit posts
	) );

	/**
	 *
	 *
	 * @param unknown $thing_slug  string Slug of the field or group
	 * @param unknown $thing       object Field or Group args set up when registering
	 * @param unknown $object_type string What type of object (post, comment, user)
	 * @param unknown $object_id   int|string ID of the object
	 * @param unknown $object_slug string
	 */
	function wpm_example_custom_exclude_callback( $thing_slug, $thing, $object_type, $object_id, $object_slug ) {

		// exclude from all posts that are in the aside category
		return in_category( 'aside', $object_id );
	}

	register_post_field( 'wpm_example_fieldIncludedCallback', 'post', array(
		'description' => 'This field is included using a custom callback; will only be included for posts that are not published',
		'label'       => 'Included Field (with callback)',
		'include'     => 'wpm_example_custom_include_callback',
	) );

	function wpm_example_custom_include_callback( $thing_slug, $thing, $object_type, $object_id, $object_slug ) {

		$post = get_post( $object_id );

		return 'publish' != $post->post_status;
	}
}

add_action( 'custom_metadata_manager_init_metadata', 'wpm_example_init_custom_fields' );

/**
 * this is an example of a column callback function
 * it echoes out a bogus description, but it's just so you can see how it works
 *
 * @param string $field_slug  the slug/id of the field
 * @param object $field       the field object
 * @param string $object_type what object type is the field associated with
 * @param int    $object_id   the ID of the current object
 * @param string $value       the value of the field
 *
 * @return void
 */
function wpm_example_field_name2_callback( $field_slug, $field, $object_type, $object_id, $value ) {

	echo sprintf( 'The value of field "%s" is %s. <br /><a href="http://icanhascheezburger.files.wordpress.com/2010/10/04dc84b6-3dde-45db-88ef-f7c242731ce3.jpg">Here\'s a LOLCat</a>', $field_slug, $value ? $value : 'not set' );
}

/**
 * this is another example of a custom callback function
 * we've chosen not to include all of the params this time
 *
 * @param string $field_slug  the slug/id of the field
 * @param object $field       the field object
 * @param string $object_type what object type is the field associated with
 *
 * @return void
 */
function wpm_example_field_custom_hidden_1_callback( $field_slug, $field, $value ) {

	if ( ! $value ) {
		$value = 'This is a secret hidden value! Don\'t tell anyone!';
	}
	?>
	<hr />
	<p>This is a hidden field rendered with a custom callback. The value is "<?php echo $value; ?>".</p>
	<input type="hidden" name="<?php echo $field_slug; ?>" value="<?php echo $value; ?>" />
	<hr />
<?php
}

/**
 * Register a test post type just for the examples
 *
 * @return void
 */
function wpm_example_init_custom_post_type() {

	$labels = array(
		'name'               => _x( 'Tests', 'post type general name' ),
		'singular_name'      => _x( 'Test', 'post type singular name' ),
		'add_new'            => _x( 'Add New', 'Test' ),
		'add_new_item'       => __( 'Add New Test' ),
		'edit_item'          => __( 'Edit Test' ),
		'new_item'           => __( 'New Test' ),
		'all_items'          => __( 'All Tests' ),
		'view_item'          => __( 'View Test' ),
		'search_items'       => __( 'Search Tests' ),
		'not_found'          => __( 'No Tests found' ),
		'not_found_in_trash' => __( 'No Tests found in Trash' ),
		'parent_item_colon'  => '',
		'menu_name'          => 'Tests'

	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => true,
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title' )
	);

	register_post_type( 'wpm_example_test', $args );

	// other types here

}

add_action( 'init', 'wpm_example_init_custom_post_type' );