<?php
/**
 * The default post_category field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$display_value  = $gravityview->display_value;
$value          = $gravityview->value;
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

if ( ! empty( $field_settings['dynamic_data'] ) ) {

	$term_list = gravityview_get_the_term_list( $entry['post_id'], $field_settings['link_to_term'], 'category' );

	if ( empty( $term_list ) ) {
		do_action( 'gravityview_log_debug', 'Dynamic data for post #' . $entry['post_id'] . ' doesnt exist.' );
	}

	echo $term_list;

} elseif ( empty( $field_settings['link_to_term'] ) ) {


		echo wp_kses(
			$display_value,
			array(
				'ul' => array( 'class' => true ),
				'li' => array(),
			)
		);

} else {

	echo gravityview_convert_value_to_term_list( $value, 'category' );
}
