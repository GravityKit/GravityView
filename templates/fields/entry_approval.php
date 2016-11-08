<?php
/**
 * Approval field output
 *
 * This field will only render for users with the `gravityview_moderate_entries` capability.
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 * @since 1.19
 */

/**
 * @action `gravityview/field/approval/load_scripts` Trigger loading the field approval javascript
 * @see GravityView_Field_Approval::enqueue_and_localize_script
 * @since 1.19
 */
do_action( 'gravityview/field/approval/load_scripts' );

$entry = GravityView_View::getInstance()->getCurrentEntry();
$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );
$current_status = GravityView_Entry_Approval::get_entry_status( $entry, 'value' );
$anchor = GravityView_Field_Entry_Approval::get_anchor_text( $current_status );
$title = GravityView_Field_Entry_Approval::get_title_attr( $current_status );
$class = GravityView_Field_Entry_Approval::get_css_class( $current_status );
?>
<a href="#" aria-role="button" aria-live="polite" aria-busy="false" class="gv-approval-toggle <?php echo $class; ?>" title="<?php echo esc_attr( $title ); ?>" data-current-status="<?php echo esc_attr( $current_status ); ?>" data-entry-slug="<?php echo esc_attr( $entry_slug ); ?>" data-form-id="<?php echo esc_attr( $entry['form_id'] ); ?>"><span class="screen-reader-text"><?php echo $anchor; ?></span></a>