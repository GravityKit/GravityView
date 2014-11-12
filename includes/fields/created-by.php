<?php

class GravityView_Field_Created_By extends GravityView_Field {

	var $name = 'created_by';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( 'edit' === $context ) {
			return $field_options;
		}

		$field_options['name_display'] = array(
			'type' => 'select',
			'label' => __( 'User Format', 'gravityview' ),
			'desc' => __( 'How should the User information be displayed?', 'gravityview'),
			'choices' => array(
				'display_name' => __('Display Name (Example: "Ellen Ripley")', 'gravityview'),
				'user_login' => __('Username (Example: "nostromo")', 'gravityview'),
				'ID' => __('User ID # (Example: 426)', 'gravityview'),
			),
			'value' => 'display_name'
		);

		return $field_options;
	}

}

new GravityView_Field_Created_By;
