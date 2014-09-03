<?php

/**
 * Add custom options for source_url fields
 */
class GravityView_Field_Source_URL extends GravityView_Field {

	var $name = 'source_url';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Don't link to entry; doesn't make sense.
		unset( $field_options['show_as_link'] );

		$add_options = array();
		$add_options['link_to_source'] = array(
			'type' => 'checkbox',
			'label' => __( 'Link to URL:', 'gravity-view' ),
			'desc' => __('Display as a link to the Source URL', 'gravity-view'),
			'default' => false,
			'merge_tags' => false,
		);
		$add_options['source_link_text'] = array(
			'type' => 'text',
			'label' => __( 'Link Text:', 'gravity-view' ),
			'desc' => __('Customize the link text. If empty, the link text will be the the URL.', 'gravity-view'),
			'default' => NULL,
			'merge_tags' => true,
		);

		return $add_options + $field_options;
	}

}

new GravityView_Field_Source_URL;
