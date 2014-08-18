<?php

/**
 * Add custom options for date fields
 */
class GravityView_Field_Website extends GravityView_Field {

	var $name = 'website';

	function field_options( $field_options, $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

if ( empty = do not display the field at all and do not run the truncatelink function )


if (!empty )		// It makes no sense to use this as the link.
		unset( $field_options['show_as_link'] );

		$field_options['truncatelink'] = array(
			'type' => 'checkbox',
			'default' => true,
			'label' => __( 'Shorten Link Display', 'gravity-view' ),
			'tooltip' => __( 'Only show the domain for a URL instead of the whole link.', 'gravity-view' ),
			'desc' => __( 'Don&rsquo;t show the full URL, only show the domain.', 'gravity-view' )
		);

		return $field_options;
	}

}

new GravityView_Field_Website;
