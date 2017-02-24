<?php
/**
 * @file class-gravityview-field-website.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for date fields
 */
class GravityView_Field_Website extends GravityView_Field {

	var $name = 'website';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'contains', 'starts_with', 'ends_with' );

	/** @see GF_Field_Website */
	var $_gf_field_class_name = 'GF_Field_Website';

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'Website', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		// It makes no sense to use this as the link.
		unset( $field_options['show_as_link'] );

		if( 'edit' === $context ) {
			return $field_options;
		}

		/**
		 * @since 1.8
		 */
		$field_options['anchor_text'] = array(
			'type' => 'text',
			'label' => __( 'Link Text:', 'gravityview' ),
			'desc' => __( 'Define custom link text. Leave blank to display the URL', 'gravityview' ),
			'value' => '',
			'merge_tags' => 'force',
		);

		$field_options['truncatelink'] = array(
			'type' => 'checkbox',
			'value' => true,
			'label' => __( 'Shorten Link Display', 'gravityview' ),
			'tooltip' => __( 'Only show the domain for a URL instead of the whole link.', 'gravityview' ),
			'desc' => __( 'Don&rsquo;t show the full URL, only show the domain.', 'gravityview' )
		);

		$field_options['open_same_window'] = array(
			'type' => 'checkbox',
			'value' => false,
			'label' => __( 'Open link in the same window?', 'gravityview' ),
		);

		return $field_options;
	}

}

new GravityView_Field_Website;
