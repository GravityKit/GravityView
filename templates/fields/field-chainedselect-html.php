<?php
/**
 * The default Chained Select field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

echo gravityview_get_field_value( $gravityview->entry->as_entry(), $gravityview->field->ID, $gravityview->display_value );