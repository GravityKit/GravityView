<?php

/**
 * Add custom options for email fields
 */
class GravityView_Field_Email extends GravityView_Field {

	var $name = 'email';

	function field_options( $field_options, $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		// It makes no sense to use this as the link.
		unset( $field_options['show_as_link'] );

		$email_options = array(
			'emailmailto' => array(
				'type' => 'checkbox',
				'default' => true,
				'label' => __( 'Link the Email Address', 'gravity-view' ),
				'desc' => __( 'Link the an email when cliking an email when clicked.', 'gravity-view' ),
			),
			'emailsubject' => array(
				'type' => 'text',
				'label' => __( 'Email Subject', 'gravity-view' ),
				'default' => '',
				'desc' => __( 'Set the default email subject line.', 'gravity-view' ),
				'merge_tags' => 'force',
			),
			'emailbody' => array(
				'type' => 'textarea',
				'label' => __( 'Email Body', 'gravity-view' ),
				'default' => '',
				'desc' => __( 'Set the default email content.', 'gravity-view' ),
				'merge_tags' => 'force',
			),
			'emailencrypt' => array(
				'type' => 'checkbox',
				'default' => true,
				'label' => __( 'Encrypt Email Address', 'gravity-view' ),
				'desc' => __( 'Make it harder for spammers to get email addresses from your entries. Email addresses will not be visible with Javascript disabled.', 'gravity-view' )
			)
		);

		return $email_options + $field_options;
	}

}

new GravityView_Field_Email;
