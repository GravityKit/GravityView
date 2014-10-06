<?php

/**
 * Add custom options for Code field
 * @since 1.2
 */
class GravityView_Field_Custom extends GravityView_Field {

	var $name = 'custom';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset ( $field_options['search_filter'], $field_options['show_as_link'] );

		$new_fields = array(
			'content' => array(
				'type' => 'textarea',
				'label' => __( 'Custom Content', 'gravityview' ),
				'desc' => __( 'Enter text or HTML. Also supports shortcodes.', 'gravityview' ),
				'default' => '',
				'class'	=> 'code',
				'merge_tags' => 'force',
			),
			'wpautop' => array(
				'type' => 'checkbox',
				'label' => __( 'Automatically add paragraphs to content', 'gravityview' ),
				'tooltip' => __( 'Wrap each block of text in an HTML paragraph tag (recommended for text).', 'gravityview' ),
				'default' => '',
			),
		);

		return $new_fields + $field_options;
	}

}

new GravityView_Field_Custom;
