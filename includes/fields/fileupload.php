<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_FileUpload extends GravityView_Field {

	var $name = 'fileupload';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset( $field_options['search_filter'] );

		$add_options['link_to_file'] = array(
			'type' => 'checkbox',
			'label' => __( 'Display as a Link:', 'gravity-view' ),
			'desc' => __('Display the uploaded files as links, rather than embedded content.', 'gravity-view'),
			'default' => false,
			'merge_tags' => false,
		);

		return $add_options + $field_options;
	}

}

new GravityView_Field_FileUpload;
