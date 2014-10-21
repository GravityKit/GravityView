<?php
/**
 * Display the Section field type
 *
 * @package GravityView
 */

global $gravityview_view;

extract( $gravityview_view->field_data );

// Tell the renderer not to wrap this field in an anchor tag.
$gravityview_view->field_data['field_settings']['show_as_link'] = false;

if( !empty( $field['description'] ) ) {
	echo GravityView_API::replace_variables( $field['description'], $form, $entry );
}
