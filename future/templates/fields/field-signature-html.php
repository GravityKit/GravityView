<?php
/**
 * The default checkbox field output template.
 *
 * @since future
 */
$field_id = $gravityview->field->ID;
$field = $gravityview->field->field;
$value = $gravityview->value;
$form = $gravityview->view->form->form;
$display_value = $gravityview->display_value;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

// If empty, there's no signature to show
if ( empty( $value ) ) { return; }

if ( ! class_exists( 'GFSignature' ) ) {
	gravityview()->log->error( '[fields/signature.php] GFSignature not loaded.' );
	return;
}

$image_atts = array(
	'src' => GFSignature::get_instance()->get_signature_url( $value ),
	'width' => ( rgblank( rgget( "boxWidth", $field ) ) ? '300' : rgar( $field, "boxWidth" ) ), // Taken from signature addon signature_input() method
	'height' => '180', // Always 180
	'validate_src' => false, // Don't check if there's a valid image extension
	'alt' => '',
);

echo new GravityView_Image( $image_atts );
