<?php
/**
 * The default custom content field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_settings = $gravityview->field->as_configuration();

echo $gravityview->field->field->get_sequence( $gravityview );
