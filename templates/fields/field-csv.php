<?php
/**
 * The default field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */
$field_id = $gravityview->field->ID;
$display_value = $gravityview->display_value;
$entry = $gravityview->entry->as_entry();

echo gravityview_get_field_value( $entry, $field_id, $display_value );
