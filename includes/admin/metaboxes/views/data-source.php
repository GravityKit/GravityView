<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/views
 * @since 1.8
 * @global WP_Post $post
 */

// Use nonce for verification
wp_nonce_field( 'gravityview_select_form', 'gravityview_select_form_nonce' );

// current value
$current_form = (int) \GV\Utils::_GET( 'form_id', gravityview_get_form_id( $post->ID ) );

// If form is in trash or not existing, show error
GravityView_Admin::connected_form_warning( $current_form );

/**
 * Modify the default orderby field for the Data Source dropdown.
 *
 * @since 2.17.8
 * @param mixed $order_by Either the field name to order by or an array of multiple orderby fields as $orderby => $order.
 */
$order_by = apply_filters( 'gk/gravityview/metaboxes/data-source/order-by', 'title' );

$forms = GVCommon::get_forms_as_options( null, false, $order_by );

/**
 * @param int    $current_form Form currently selected in the View (0 if none selected)
 * @param array  $forms Array of active forms, not in trash
 * @since 1.22.1
 * @since 2.39   Modified the $forms array to only include the form ID as key and title as value, not full form objects.
 */
do_action( 'gravityview/metaboxes/data-source/before', $current_form, $forms );

?>
<label for="gravityview_form_id" ><?php esc_html_e( 'Where would you like the data to come from for this View?', 'gk-gravityview' ); ?></label>

<p>
	<?php

	if ( empty( $current_form ) && GVCommon::has_cap( 'gravityforms_create_form' ) ) {
		?>
		<a class="button button-primary" href="#gv_start_fresh" title="<?php esc_attr_e( 'Use a Form Preset', 'gk-gravityview' ); ?>"><?php esc_html_e( 'Use a Form Preset', 'gk-gravityview' ); ?></a>

		<?php if ( ! empty( $forms ) ) { ?>
			<span>&nbsp;<?php esc_html_e( 'or use an existing form', 'gk-gravityview' ); ?>&nbsp;</span>
			<?php
		}
	}

	// If there are no forms to select, show no forms.
	if ( ! empty( $forms ) ) {
		?>
		<select name="gravityview_form_id" id="gravityview_form_id">
			<option value="" <?php selected( '', $current_form, true ); ?>>&mdash; <?php esc_html_e( 'list of forms', 'gk-gravityview' ); ?> &mdash;</option>
			<?php foreach ( $forms as $id => $title ) { ?>
				<option value="<?php echo $id; ?>" <?php selected( $id, $current_form, true ); ?>>
					<?php
					echo esc_html( $title );
					?>
				</option>
			<?php } ?>
		</select>
	<?php } else { ?>
		<select name="gravityview_form_id" id="gravityview_form_id" class="hidden"><option selected="selected" value=""></option></select>
	<?php } ?>

	<button class="button button-primary" style="display:none;" id="gv_switch_view_button" title="<?php esc_attr_e( 'Switch View', 'gk-gravityview' ); ?>"><?php esc_html_e( 'Switch View Type', 'gk-gravityview' ); ?></button>
</p>

<?php // confirm dialog box ?>
<div id="gravityview_change_form_dialog" class="gv-dialog-options gv-dialog-warning" title="<?php esc_attr_e( 'Attention', 'gk-gravityview' ); ?>">
	<p><?php esc_html_e( 'Changing the form will reset your field configuration. Changes will be permanent once you save the View.', 'gk-gravityview' ); ?></p>
</div>

<?php // confirm template dialog box ?>
<div id="gravityview_switch_template_dialog" class="gv-dialog-options gv-dialog-warning" title="<?php esc_attr_e( 'Attention', 'gk-gravityview' ); ?>">
	<p><?php esc_html_e( 'Changing the View Type will reset your field configuration. Changes will be permanent once you save the View.', 'gk-gravityview' ); ?></p>
</div>

<?php // confirm template dialog box ?>
	<div id="gravityview_select_preset_dialog" class="gv-dialog-options gv-dialog-warning" title="<?php esc_attr_e( 'Attention', 'gk-gravityview' ); ?>">
		<p><?php esc_html_e( 'Using a preset will reset your field configuration. Changes will be permanent once you save the View.', 'gk-gravityview' ); ?></p>
	</div>

<?php // no js notice ?>
<div class="error hide-if-js">
	<p><?php esc_html_e( 'GravityView requires Javascript to be enabled.', 'gk-gravityview' ); ?></p>
</div>

<?php
// hidden field to keep track of start fresh state
?>
<input type="hidden" id="gravityview_form_id_start_fresh" name="gravityview_form_id_start_fresh" value="0" />

<?php

/**
 * @param int    $current_form Form currently selected in the View (0 if none selected)
 * @param array  $forms Array of active forms, not in trash
 * @since 1.22.1
 * @since 2.39   Modified the $forms array to only include the form ID and title, not full form objects.
 */
do_action( 'gravityview/metaboxes/data-source/after', $current_form, $forms );
