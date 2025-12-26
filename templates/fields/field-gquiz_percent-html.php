<?php
/**
 * The default quiz percentage field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$display_value = $gravityview->display_value;

// If there's no grade, don't continue
if ( gv_empty( $display_value, false, false ) ) {
	return;
}

/**
 * Modify the format of the display of Quiz Score (Percent) field.
 *
 * @since 2.0
 *
 * @see http://php.net/manual/en/function.sprintf.php For formatting guide.
 *
 * @param string               $format      Format passed to printf() function. Default `%d%%`, which prints as "{number}%". Notice the double `%%`, this prints a literal '%' character.
 * @param \GV\Template_Context $gravityview The template context.
 */
$format = apply_filters( 'gravityview/field/quiz_percent/format', '%d%%', $gravityview );

printf( $format, $display_value );
