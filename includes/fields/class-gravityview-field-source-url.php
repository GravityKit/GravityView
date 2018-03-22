<?php
/**
 * @file class-gravityview-field-source-url.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for source_url fields
 */
class GravityView_Field_Source_URL extends GravityView_Field {

	var $name = 'source_url';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'contains', 'starts_with', 'ends_with' );

	var $group = 'meta';

	public function __construct() {
		$this->label = esc_html__( 'Source URL', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Don't link to entry; doesn't make sense.
		unset( $field_options['show_as_link'] );

		if( 'edit' === $context ) {
			return $field_options;
		}

		$add_options = array();
		$add_options['link_to_source'] = array(
			'type' => 'checkbox',
			'label' => __( 'Link to URL:', 'gravityview' ),
			'desc' => __('Display as a link to the Source URL', 'gravityview'),
			'value' => false,
			'merge_tags' => false,
		);
		$add_options['source_link_text'] = array(
			'type' => 'text',
			'label' => __( 'Link Text:', 'gravityview' ),
			'desc' => __('Customize the link text. If empty, the link text will be the URL.', 'gravityview'),
			'value' => NULL,
			'merge_tags' => true,
		);

		return $add_options + $field_options;
	}

}

new GravityView_Field_Source_URL;
