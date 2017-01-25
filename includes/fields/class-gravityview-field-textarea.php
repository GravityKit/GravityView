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

	public function __construct() {
		$this->label = esc_html__( 'Paragraph Text', 'gravityview' );
		parent::__construct();
	}

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

        $field_options['make_clickable'] = array(
            'type' => 'checkbox',
            'merge_tags' => false,
            'value' => 0,
            'label' => __( 'Convert text URLs to HTML links', 'gravityview' ),
            'tooltip' => __( 'Converts URI, www, FTP, and email addresses in HTML links', 'gravityview' ),
        );

		return $field_options;
	}

}

new GravityView_Field_Textarea;
