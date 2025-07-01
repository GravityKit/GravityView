<?php
/**
 * @file class-gravityview-field-post-category.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Post_Category extends GravityView_Field {

	var $name = 'post_category';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains' );

	var $_gf_field_class_name = 'GF_Field_Post_Category';

	var $group = 'post';

	var $icon = 'dashicons-category';

	public function __construct() {
		$this->label = esc_html__( 'Post Category', 'gk-gravityview' );

		add_action( 'gravityview/edit-entry/render/before', array( $this, 'add_edit_entry_post_category_choices_filter' ) );

		add_action( 'gravityview/edit-entry/render/after', array( $this, 'remove_edit_entry_post_category_choices_filter' ) );

		add_action( 'gravityview/edit_entry/after_update', array( $this, 'set_post_categories' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Update the post categories based on all post category fields
	 *
	 * @since 1.17
	 *
	 * @param array $form Gravity Forms form array
	 * @param int   $entry_id Numeric ID of the entry that was updated
	 *
	 * @return array|false|WP_Error Array of term taxonomy IDs of affected categories. WP_Error or false on failure. false if there are no post category fields or connected post.
	 */
	public function set_post_categories( $form = array(), $entry_id = 0 ) {

		$entry = GFAPI::get_entry( $entry_id );
		$post_id = GVCommon::get_post_id_from_entry( $entry );

		if ( empty( $post_id ) ) {
			return false;
		}

		$return = false;

		$post_category_fields = GFAPI::get_fields_by_type( $form, 'post_category' );

		if ( $post_category_fields ) {

			$updated_categories = array();

			foreach ( $post_category_fields as $field ) {
				// Get the value of the field, including $_POSTed value
				$field_cats         = RGFormsModel::get_field_value( $field );
				$field_cats         = is_array( $field_cats ) ? array_values( $field_cats ) : (array) $field_cats;
				$field_cats         = gv_map_deep( $field_cats, 'intval' );
				$updated_categories = array_merge( $updated_categories, array_values( $field_cats ) );
			}

			// Remove `0` values from intval()
			$updated_categories = array_filter( $updated_categories );

			/**
			 * Should post categories be added to or replaced?
			 *
			 * @since 1.17
			 * @param bool $append If `true`, don't delete existing categories, just add on. If `false`, replace the categories with the submitted categories. Default: `false`
			 */
			$append = apply_filters( 'gravityview/edit_entry/post_categories/append', false );

			$return = wp_set_post_categories( $post_id, $updated_categories, $append );
		}

		return $return;
	}

	/**
	 * Add filter to show live category choices in Edit Entry
	 *
	 * @see edit_entry_post_category_choices
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	function add_edit_entry_post_category_choices_filter() {
		add_filter( 'gform_post_category_choices', array( $this, 'edit_entry_post_category_choices' ), 10, 3 );
	}

	/**
	 * Remove filter to show live category choices in Edit Entry
	 *
	 * @see edit_entry_post_category_choices
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	function remove_edit_entry_post_category_choices_filter() {
		remove_filter( 'gform_post_category_choices', array( $this, 'edit_entry_post_category_choices' ), 10 );
	}

	/**
	 * Always show the live Category values
	 *
	 * By default, Gravity Forms would show unchecked/default choices. We want to show the live Post categories
	 *
	 * @since 1.17
	 *
	 * @param $choices
	 * @param $field
	 * @param $form_id
	 *
	 * @return mixed
	 */
	function edit_entry_post_category_choices( $choices, $field, $form_id ) {

		$entry = GravityView_Edit_Entry::getInstance()->instances['render']->get_entry();
		$post_id = $entry ? GVCommon::get_post_id_from_entry( $entry ) : false;

		if ( $post_id ) {

			$post_categories = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );

			// Always use the live value
			foreach ( $choices as &$choice ) {
				$choice['isSelected'] = in_array( $choice['value'], array_values( $post_categories ) );
			}
		}

		return $choices;
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		if ( 'edit' === $context ) {
			return $field_options;
		}

		$this->add_field_support( 'dynamic_data', $field_options );
		$this->add_field_support( 'link_to_term', $field_options );
		$this->add_field_support( 'new_window', $field_options );

		$field_options['new_window']['requires'] = 'link_to_term';

		return $field_options;
	}
}

new GravityView_Field_Post_Category();
