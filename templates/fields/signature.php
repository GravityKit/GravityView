<?php
/**
 * Display the signature field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

// If empty, there's no signature to show
if( empty( $value ) ) { return; }

if( !class_exists( 'GFSignature' ) ) {
	do_action('gravityview_log_error', '[fields/signature.php] GFSignature not loaded.');
	return;
}

$image_atts = array(
	'src' => GFSignature::get_instance()->get_signature_url( $value ),
	'width' => ( rgblank(rgget("boxWidth", $field)) ? '300' : rgar($field, "boxWidth") ), // Taken from signature addon signature_input() method
	'height' => '180', // Always 180
	'validate_src' => false, // Don't check if there's a valid image extension
	'alt' => '',
);

echo new GravityView_Image( $image_atts );
