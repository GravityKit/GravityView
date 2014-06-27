<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_FileUpload extends GravityView_Field {

	var $name = 'fileupload';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset( $field_options['search_filter'] );

		return $field_options;
	}

}

new GravityView_Field_FileUpload;
