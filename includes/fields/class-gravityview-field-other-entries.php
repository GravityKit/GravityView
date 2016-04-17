<?php
/**
 * @file class-gravityview-field-other-entries.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.7.2
 */

/**
 * A field that displays other entries by the entry_creator for the same View in a list format
 *
 * @since 1.7.2
 */
class GravityView_Field_Other_Entries extends GravityView_Field {

	var $name = 'other_entries';

	var $is_searchable = false;

	var $contexts = array( 'multiple', 'single' );

	var $group = 'gravityview';

	public function __construct() {
		$this->label = esc_html__( 'Other Entries', 'gravityview' );
		$this->description = esc_html__('Display other entries created by the entry creator.', 'gravityview');
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 * @since 1.7.2
	 */
	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		// No "Link to single entry"; all the items will be links to entries!
		unset( $field_options['show_as_link'] );

		$new_options = array();

		$new_options['link_format'] = array(
			'type'  => 'text',
			'label' => __( 'Entry link text (required)', 'gravityview' ),
			'value' => __('Entry #{entry_id}', 'gravityview'),
			'merge_tags' => 'force',
		);

		$new_options['after_link'] = array(
			'type'  => 'textarea',
			'label' => __( 'Text or HTML to display after the link (optional)', 'gravityview' ),
			'desc'  => __('This content will be displayed below each entry link.', 'gravityview'),
			'value' => '',
			'merge_tags' => 'force',
			'class' => 'widefat code',
		);

		$new_options['page_size'] = array(
			'type'  => 'number',
			'label' => __( 'Entries to Display', 'gravityview' ),
			'desc'  => __( 'What is the maximum number of entries that should be shown?', 'gravityview' ),
			'value' => '10',
			'merge_tags' => false,
		);

		$new_options['no_entries_hide'] = array(
			'type'  => 'checkbox',
			'label' => __( 'Hide if no entries', 'gravityview' ),
			'desc'  => __( 'Don\'t display this field if the entry creator has no other entries', 'gravityview' ),
			'value' => false,
		);

		$new_options['no_entries_text'] = array(
			'type'  => 'text',
			'label' => __( 'No Entries Text', 'gravityview' ),
			'desc'  => __( 'The text that is shown if the entry creator has no other entries (and "Hide if no entries" is disabled).', 'gravityview' ),
			'value' => __( 'This user has no other entries.', 'gravityview' ),
		);

		return $new_options + $field_options;
	}

}

new GravityView_Field_Other_Entries;
