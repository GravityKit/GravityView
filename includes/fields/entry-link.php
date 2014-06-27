<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_Entry_Link extends GravityView_Field {

	var $name = 'entry_link';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Always a link!
		unset( $field_options['show_as_link'] );

		$add_options = array();
		$add_options['entry_link_text'] = array(
			'type' => 'text',
			'label' => __( 'Link Text:', 'gravity-view' ),
			'desc' => NULL,
			'default' => __('View Details', 'gravity-view'),
			'merge_tags' => true,
		);

		return $add_options + $field_options;
	}

}

new GravityView_Field_Entry_Link;
