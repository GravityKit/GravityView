<?php
/**
 * Display Gravity Forms Quiz value Pass/Fail
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$field = GravityView_View::getInstance()->getCurrentField();

// If there's no grade, don't continue
if( empty( $field['value'] ) ) {
	return;
}

/**
 * Modify the format of the display of the field.
 *
 * Notice the double `%%`, this prints a literal '%' character
 *
 * @see http://php.net/manual/en/function.sprintf.php For formatting guide
 *
 * @param string $format Format passed to printf() function. Default `%d%%`, which prints as "{number}%"
 */
$format = apply_filters('gravityview/field/quiz_percent/format', '%d%%');

printf( $format, $field['value'] );