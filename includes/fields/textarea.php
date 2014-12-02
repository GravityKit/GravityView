<?php

/**
 * Add custom options for textarea fields
 */
class GravityView_Field_Textarea extends GravityView_Field {

	var $name = 'textarea';

	function field_options( $field_options, $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$field_options['trim_words'] = array(
			'type' => 'number',
			'merge_tags' => false,
			'value' => null,
			'label' => __( 'Maximum words shown', 'gravityview' ),
			'tooltip' => __( 'Enter the number of words to be shown. If specified it truncates the text. Leave it blank if you want to show the full text.', 'gravityview' ),
		);

		return $field_options;
	}

}

new GravityView_Field_Textarea;
