<?php
/**
 * @file class-gravityview-field-email.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add custom options for email fields
 */
class GravityView_Field_Email extends GravityView_Field {

	var $name = 'email';

	var $is_searchable = true;

	var $search_operators = array( 'is', 'isnot', 'contains', 'starts_with', 'ends_with' );

	var $_gf_field_class_name = 'GF_Field_Email';

	var $group = 'advanced';

	public function __construct() {
		$this->label = esc_html__( 'Email', 'gravityview' );
		parent::__construct();
	}

	function field_options( $field_options, $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		// It makes no sense to use this as the link.
		unset( $field_options['show_as_link'] );

		if( 'edit' === $context ) {
			return $field_options;
		}

		$email_options = array(
			'emailmailto' => array(
				'type' => 'checkbox',
				'value' => true,
				'label' => __( 'Link the Email Address', 'gravityview' ),
				'desc' => __( 'Clicking the link will generate a new email.', 'gravityview' ),
			),
			'emailsubject' => array(
				'type' => 'text',
				'label' => __( 'Email Subject', 'gravityview' ),
				'value' => '',
				'desc' => __( 'Set the default email subject line.', 'gravityview' ),
				'merge_tags' => 'force',
			),
			'emailbody' => array(
				'type' => 'textarea',
				'label' => __( 'Email Body', 'gravityview' ),
				'value' => '',
				'desc' => __( 'Set the default email content.', 'gravityview' ),
				'merge_tags' => 'force',
				'class' => 'widefat',
			),
			'emailencrypt' => array(
				'type' => 'checkbox',
				'value' => true,
				'label' => __( 'Encrypt Email Address', 'gravityview' ),
				'desc' => __( 'Make it harder for spammers to get email addresses from your entries. Email addresses will not be visible with Javascript disabled.', 'gravityview' )
			)
		);

		return $email_options + $field_options;
	}

}

new GravityView_Field_Email;
