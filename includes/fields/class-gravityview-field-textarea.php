<?php
/**
 * @file class-gravityview-field-textarea.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for textarea fields
 */
class GravityView_Field_Textarea extends GravityView_Field {

	var $name = 'textarea';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'contains', 'starts_with', 'ends_with' );

	var $_gf_field_class_name = 'GF_Field_Textarea';

	var $group = 'standard';

	var $icon = 'dashicons-editor-paragraph';

	public function __construct() {
		$this->label = esc_html__( 'Paragraph Text', 'gk-gravityview' );
		parent::__construct();
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		if ( 'edit' === $context ) {
			return $field_options;
		}

		unset( $field_options['show_as_link'] );

		$field_options['trim_words'] = array(
			'type'       => 'number',
			'merge_tags' => false,
			'value'      => null,
			'label'      => __( 'Maximum words shown', 'gk-gravityview' ),
			'tooltip'    => __( 'Enter the number of words to be shown. If specified it truncates the text. Leave it blank if you want to show the full text.', 'gk-gravityview' ),
		);

		$field_options['make_clickable'] = array(
			'type'       => 'checkbox',
			'merge_tags' => false,
			'value'      => 0,
			'label'      => __( 'Convert text URLs to HTML links', 'gk-gravityview' ),
			'tooltip'    => __( 'Converts URI, www, FTP, and email addresses in HTML links', 'gk-gravityview' ),
		);

		$field_options['allow_html'] = array(
			'type'       => 'checkbox',
			'merge_tags' => false,
			'value'      => 1,
			'label'      => __( 'Display as HTML', 'gk-gravityview' ),
			'tooltip'    => esc_html__( 'If enabled, safe HTML will be displayed and unsafe or unrecognized HTML tags will be stripped. If disabled, the field value will be displayed as text.', 'gk-gravityview' ),
		);

		return $field_options;
	}
}

new GravityView_Field_Textarea();
