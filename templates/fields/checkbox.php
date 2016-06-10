<?php
/**
 * Checkbox field output
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 *
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

if( in_array( $field['type'], array( 'radio', 'checkbox' ) ) && !empty( $entry[ $field_id ] ) ) {

	/**
	 * @filter `gravityview_field_tick` Change the output for a checkbox "check" symbol. Default is dashicons-yes icon
	 * @see https://developer.wordpress.org/resource/dashicons/#yes
	 * @param string $output HTML span with `dashicons dashicons-yes` class
	 * @param array $entry Gravity Forms entry array
	 * @param array $field GravityView field array
	 */
	$output = apply_filters( 'gravityview_field_tick', '<span class="dashicons dashicons-yes"></span>', $entry, $field);

} else {
	$output = gravityview_get_field_value( $entry, $field_id, $display_value );
}

echo $output;
