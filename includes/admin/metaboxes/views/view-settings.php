<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/partials
 * @global $post
 */
global $post;

$curr_form = gravityview_get_form_id( $post->ID );

// View template settings
$current_settings = gravityview_get_template_settings( $post->ID );

?>

<table class="form-table">

	<?php

	GravityView_Render_Settings::render_setting_row( 'page_size', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'lightbox', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'show_only_approved', $current_settings );

	/**
	 * @since 1.5.4
	 */
	GravityView_Render_Settings::render_setting_row( 'hide_until_searched', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'hide_empty', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'user_edit', $current_settings );

	/**
	 * @since  1.5.1
	 */
	GravityView_Render_Settings::render_setting_row( 'user_delete', $current_settings );

	do_action( 'gravityview_admin_directory_settings', $current_settings );

	?>

</table>

<h3 style="margin-top:1em;"><?php esc_html_e( 'Single Entry Settings', 'gravityview'); ?>:</h3>

<table class="form-table"><?php

	GravityView_Render_Settings::render_setting_row( 'single_title', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'back_link_label', $current_settings );

	?>
</table>