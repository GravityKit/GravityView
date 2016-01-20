<?php
/**
 * @file class-gravityview-field-entry-link.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for entry_link fields
 */
class GravityView_Field_Entry_Link extends GravityView_Field {

	var $name = 'entry_link';

	var $contexts = array( 'multiple' );

	/**
	 * @var bool
	 * @since 1.15.3
	 */
	var $is_sortable = false;

	/**
	 * @var bool
	 * @since 1.15.3
	 */
	var $is_searchable = false;

	var $group = 'gravityview';

	public function __construct() {
		$this->label = esc_html__( 'Link to Entry', 'gravityview' );
		$this->description = esc_html__('A dedicated link to the single entry with customizable text.', 'gravityview');
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Always a link!
		unset( $field_options['show_as_link'], $field_options['search_filter'] );

		if( 'edit' === $context ) {
			return $field_options;
		}

		$add_options = array();
		$add_options['entry_link_text'] = array(
			'type' => 'text',
			'label' => __( 'Link Text:', 'gravityview' ),
			'desc' => NULL,
			'value' => __('View Details', 'gravityview'),
			'merge_tags' => true,
		);

		return $add_options + $field_options;
	}

}

new GravityView_Field_Entry_Link;
