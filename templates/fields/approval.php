<?php
/**
 * Approval field output
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 *
 */

/**
 * @action `gravityview/field/approval/load_scripts` Trigger loading the field approval javascript
 * @see GravityView_Field_Approval::enqueue_and_localize_script
 * @since TODO
 */
do_action( 'gravityview/field/approval/load_scripts' );

$entry = GravityView_View::getInstance()->getCurrentEntry();

$approved = gform_get_meta( $entry['id'], 'is_approved' );

$strings = GravityView_Field_Approval::get_strings();

if( ! empty( $approved ) ) {
	$anchor = $strings['label_disapprove'];
	$title = $strings['unapprove_title'];
	$class = 'entry_approved';
} else {
	$title = $strings['approve_title'];
	$anchor = $strings['label_approve'];
	$class = '';
}

?>
<a href="#" class="toggleApproved <?php echo $class; ?>" title="<?php echo $title; ?>" data-approved-status="<?php echo $approved; ?>" data-entry-id="<?php echo esc_attr( $entry['id'] ); ?>" data-form-id="<?php echo esc_attr( $entry['form_id'] ); ?>"><?php echo $anchor; ?></a>