<?php
/**
 * The default signature field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field = $gravityview->field->field;
$value = $gravityview->value;

// If empty, there's no signature to show
if ( empty( $value ) ) {
	return; }

if ( ! class_exists( 'GFSignature' ) ) {
	gravityview()->log->error( '[fields/signature.php] GFSignature not loaded.' );
	return;
}

$image_atts = array(
	'src'          => GFSignature::get_instance()->get_signature_url( $value ),
	'width'        => \GV\Utils::_GET( 'boxWidth', \GV\Utils::get( $field, 'boxWidth', 300 ) ), // Taken from signature addon signature_input() method
	'height'       => '180', // Always 180
	'validate_src' => false, // Don't check if there's a valid image extension
	'alt'          => '',
);

echo new GravityView_Image( $image_atts );
