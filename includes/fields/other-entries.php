<?php

/**
 * A field that displays other entries by the entry_creator for the same View in a list format
 */
class GravityView_Field_Other_Entries extends GravityView_Field {

	var $name = 'other_entries';

	/**
	 * @inheritDoc
	 */
	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$field_options['link_format'] = array(
			'type'  => 'text',
			'label' => __( 'Entry link text (required)', 'gravityview' ),
			'desc'  => __( 'Text or HTML to display after the link (optional)', 'gravityview' ),
			'value' => __('Entry #{entry_id}', 'gravityview'),
		);

		$field_options['after_link'] = array(
			'type'  => 'textarea',
			'label' => __( 'Entries to Display', 'gravityview' ),
			'desc'  => __( 'Text or HTML to display after the link (optional)', 'gravityview' ),
			'value' => '',
			'class' => 'widefat code',
		);

		$field_options['page_size'] = array(
			'type'  => 'number',
			'label' => __( 'Entries to Display', 'gravityview' ),
			'desc'  => __( 'What is the maximum number of entries that should be shown?', 'gravityview' ),
			'value' => '10',
			'merge_tags' => false,
		);

		$field_options['no_entries_hide'] = array(
			'type'  => 'checkbox',
			'label' => __( 'Hide if no entries', 'gravityview' ),
			'desc'  => __( 'Don\'t display this field if the entry creator has no other entries', 'gravityview' ),
			'value' => false,
		);

		$field_options['no_entries_text'] = array(
			'type'  => 'text',
			'label' => __( 'No Entries Text', 'gravityview' ),
			'desc'  => __( 'If the entry creator has no other entries, the text that is shown.', 'gravityview' ),
			'value' => __( 'This user has no other entries.', 'gravityview' ),
		);

		return $field_options;
	}

}

new GravityView_Field_Other_Entries;
