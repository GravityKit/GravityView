<?php
/**
 * The default field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$display_value = $gravityview->display_value;

// Handle outliers.
if ( is_array( $display_value ) ) {
	$display_value = implode( ', ', $display_value );
}

echo $display_value;
