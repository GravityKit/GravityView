<?php
/**
 * The default output template for the Is Approved internal field
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.10
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

echo GravityView_Field_Is_Approved::get_output( $gravityview->value, $gravityview->field->as_configuration(), true );
