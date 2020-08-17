<?php
/**
 * The default survey field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field = $gravityview->field;

$display_value = $gravityview->display_value;

if ( $field->score ) {
	if ( $display_value = $gravityview->value[ $field->ID ] ) {
		list( $row, $column ) = explode( ':', $display_value );
		foreach( $field->field->choices as $choice ) {
			if ( $column === $choice['value'] ) {
				$display_value = $choice['score'];
				break;
			}
		}
	}
} else {
	// Make sure the CSS file is enqueued
	if ( class_exists( 'GFSurvey' ) && is_callable( array('GFSurvey', 'get_instance') ) ) {
		wp_register_style( 'gsurvey_css', GFSurvey::get_instance()->get_base_url() . '/css/gsurvey.css' );
		wp_print_styles( 'gsurvey_css' );
	}
}

echo $display_value;
