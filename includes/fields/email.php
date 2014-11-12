<?php

/**
 * Add custom options for email fields
 */
class GravityView_Field_Email extends GravityView_Field {

	var $name = 'email';

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
