<?php
/**
 * The default pipe (video) field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since develop
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$value          = $gravityview->value;
$display_value  = $gravityview->display_value;
$field_settings = $gravityview->field->as_configuration();

if ( ! empty( $field_settings['embed'] ) ) {
	if ( $value = @json_decode( $value ) ) {
		$no_video_description = __( 'Your browser does not support the video tag.', 'gk-gravityview' );
		printf( '<video poster="%s" width="320" height="240" controls><source src="%s" type="video/mp4">%s</video>', esc_url( $value->thumbnail ), esc_url( $value->video ), esc_html( $no_video_description ) );
	}
} elseif ( is_string( $display_value ) ) {
	echo $display_value;
}
