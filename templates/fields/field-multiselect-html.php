<?php
/**
 * The default select field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field = $gravityview->field->field;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

/**
 * @filter `gravityview/fields/select/output_label` Override whether to show the value or the label of a Select field
 * @since 1.5.2
 * @param bool $show_label True: Show the label of the Choice; False: show the value of the Choice. Default: `false`
 * @param array $entry GF Entry
 * @param GF_Field_Select $field Gravity Forms Select field
 * @since 2.0
 * @param \GV\Template_Context $gravityview The context
 */
$show_label = apply_filters( 'gravityview/fields/select/output_label', ( 'label' === \GV\Utils::get( $field_settings, 'choice_display' ) ), $entry, $field, $gravityview );

$output = $field->get_value_entry_detail( $gravityview->value, '', $show_label );

echo $output;
