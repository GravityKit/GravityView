<?php
/**
 * The GravityView View field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.19
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_settings = $gravityview->field->as_configuration();

GravityView_Field_GravityView_View::render_frontend( $field_settings, $gravityview );
