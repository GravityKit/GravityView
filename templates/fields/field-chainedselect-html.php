<?php
/**
 * The default Chained Select field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

echo gravityview_get_field_value( $gravityview->entry->as_entry(), $gravityview->field->ID, $gravityview->display_value );
