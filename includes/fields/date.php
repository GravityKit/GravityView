<?php

class GravityView_Field_Date extends GravityView_Field {

	var $name = 'date';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		$field_options['date_display'] = array(
			'type' => 'text',
			'label' => __( 'Override Date Format', 'gravity-view' ),
			'desc' => sprintf( __( 'Define how the date is displayed (using %sthe PHP date format%s)', 'gravity-view'), '<a href="https://www.php.net/manual/en/function.date.php">', '</a>' ),
			'default' => apply_filters( 'gravityview_date_format', NULL )
		);

		return $field_options;
	}

}

new GravityView_Field_Date;
