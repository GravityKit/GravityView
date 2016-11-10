<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/views
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

	GravityView_Render_Settings::render_setting_row( 'admin_show_all_statuses', $current_settings );

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

	/**
	 * @since 1.15.2
	 */
	GravityView_Render_Settings::render_setting_row( 'embed_only', $current_settings );

	do_action( 'gravityview_admin_directory_settings', $current_settings );

	?>

</table>