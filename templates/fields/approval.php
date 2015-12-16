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
$is_approved = ! empty( $approved );

$strings = GravityView_Field_Approval::get_strings();
$anchor = $is_approved ? $strings['label_disapprove'] : $strings['label_approve'];
$title = $is_approved ? $strings['unapprove_title'] : $strings['approve_title'];
$class = $is_approved ? 'gv-approval-approved' : '';

?>
<a href="#" class="gv-approval-toggle <?php echo $class; ?>" title="<?php echo $title; ?>" data-approved-status="<?php echo $approved; ?>" data-entry-id="<?php echo esc_attr( $entry['id'] ); ?>" data-form-id="<?php echo esc_attr( $entry['form_id'] ); ?>"><?php echo $anchor; ?></a>