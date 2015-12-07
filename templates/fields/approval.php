<?php
/**
 * Approval field output
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 *
 */

do_action( 'gravityview/fields/approval/load_scripts' );

$entry = GravityView_View::getInstance()->getCurrentEntry();

$approved = gform_get_meta( $entry['id'], 'is_approved' );

if( ! empty( $approved ) ) {
	$title = __( 'Entry approved for directory viewing. Click to disapprove this entry.', 'gravityview');
	$anchor = __( 'Disapprove', 'gravityview' );
	$class = 'entry_approved';
} else {
	$anchor = __( 'Approve', 'gravityview' );
	$title = __( 'Entry not approved for directory viewing. Click to approve this entry.', 'gravityview' );
	$class = '';
}

?>
<a href="#" class="toggleApproved <?php echo $class; ?>" title="<?php echo $title; ?>" data-approved-status="<?php echo $approved; ?>" data-entry-id="<?php echo esc_attr( $entry['id'] ); ?>" data-form-id="<?php echo esc_attr( $entry['form_id'] ); ?>"><?php echo $anchor; ?></a>