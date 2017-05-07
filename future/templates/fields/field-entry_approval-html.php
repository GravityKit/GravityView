<?php
/**
 * The default address field output template.
 *
 * This field will only render for users with the `gravityview_moderate_entries` capability.
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 * @since 1.19
 *
 * @since future
 */

$field_id = $gravityview->field->ID;
$field = $gravityview->field->field;
$value = $gravityview->value;
$display_value = $gravityview->display_value;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

/**
 * @action `gravityview/field/approval/load_scripts` Trigger loading the field approval javascript
 * @see GravityView_Field_Approval::enqueue_and_localize_script
 * @since 1.19
 *
 * @param object $gravityview The $gravityview field template context object.
 * @since future
 */
do_action( 'gravityview/field/approval/load_scripts', $gravityview );

$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );
$current_status = GravityView_Entry_Approval::get_entry_status( $entry, 'value' );
$anchor = GravityView_Field_Entry_Approval::get_anchor_text( $current_status );
$title = GravityView_Field_Entry_Approval::get_title_attr( $current_status );
$class = GravityView_Field_Entry_Approval::get_css_class( $current_status );

?><a href="#" aria-role="button" aria-live="polite" aria-busy="false" class="gv-approval-toggle <?php echo $class; ?>" title="<?php echo esc_attr( $title ); ?>" data-current-status="<?php echo esc_attr( $current_status ); ?>" data-entry-slug="<?php echo esc_attr( $entry_slug ); ?>" data-form-id="<?php echo esc_attr( $entry['form_id'] ); ?>"><span class="screen-reader-text"><?php echo $anchor; ?></span></a><?php
