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
				'label' => __( 'Custom Content', 'gravity-view' ),
				'desc' => __( 'Enter text or HTML. Also supports shortcodes.', 'gravity-view' ),
				'default' => '',
				'class'	=> 'code',
				'merge_tags' => 'force',
			),
			'wpautop' => array(
				'type' => 'checkbox',
				'label' => __( 'Automatically add paragraphs to content', 'gravity-view' ),
				'tooltip' => __( 'Wrap each block of text in an HTML paragraph tag (recommended for text).', 'gravity-view' ),
				'default' => '',
			),
		);

		return $new_fields + $field_options;
	}

}

new GravityView_Field_Custom;
