<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/views
 * @global $post
 */
global $post;

// View template settings
$current_settings = gravityview_get_template_settings( $post->ID );

?>

<table class="form-table striped"><?php

	GravityView_Render_Settings::render_setting_row( 'user_edit', $current_settings );

	/**
	 * @since 2.1
	 */
	GravityView_Render_Settings::render_setting_row( 'unapprove_edit', $current_settings );

	/**
	 * @since 2.2
	 */
	GravityView_Render_Settings::render_setting_row( 'edit_redirect', $current_settings );

	/**
	 * @since 2.2
	 */
	GravityView_Render_Settings::render_setting_row( 'edit_redirect_url', $current_settings );

	?>
</table>
