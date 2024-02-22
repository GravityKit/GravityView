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

	var $icon = 'dashicons-admin-links';

	public function __construct() {
		$this->label = esc_html__( 'Website', 'gk-gravityview' );
		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		// It makes no sense to use this as the link.
		unset( $field_options['show_as_link'] );

		if ( 'edit' === $context ) {
			return $field_options;
		}

		/**
		 * @since 1.8
		 */
		$field_options['anchor_text'] = array(
			'type'       => 'text',
			'label'      => __( 'Link Text:', 'gk-gravityview' ),
			'desc'       => __( 'Define custom link text. Leave blank to display the URL', 'gk-gravityview' ),
			'value'      => '',
			'merge_tags' => 'force',
			'priority'   => 1000,
		);

		$field_options['truncatelink'] = array(
			'type'     => 'checkbox',
			'value'    => true,
			'label'    => __( 'Shorten Link Display', 'gk-gravityview' ),
			'tooltip'  => __( 'Only show the domain for a URL instead of the whole link.', 'gk-gravityview' ),
			'desc'     => __( 'Don&rsquo;t show the full URL, only show the domain.', 'gk-gravityview' ),
			'priority' => 1500,
		);

		$this->add_field_support( 'new_window', $field_options );

		/**
		 * Set default to opening in new links for back-compatibility with Version 1.5.1
		 *
		 * @link https://github.com/gravityview/GravityView/commit/e12e76e2d032754227728d41e65103042d4f75ec
		 */
		$field_options['new_window']['value']    = true;
		$field_options['new_window']['priority'] = 2000;

		return $field_options;
	}
}

new GravityView_Field_Website();
