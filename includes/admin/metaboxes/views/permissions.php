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

	/**
	 * @since  1.5.1
	 */
	GravityView_Render_Settings::render_setting_row( 'user_delete', $current_settings );

	/**
	 * @since  2.5
	 */
	GravityView_Render_Settings::render_setting_row( 'user_duplicate', $current_settings );

	/**
	 * @since 1.15.2
	 */
	GravityView_Render_Settings::render_setting_row( 'embed_only', $current_settings );

	/**
	 * @since 2.0
	 */
	if ( gravityview()->plugin->supports( \GV\Plugin::FEATURE_REST ) && ( gravityview()->plugin->settings->get( 'rest_api' ) === '1' ) ) {
		GravityView_Render_Settings::render_setting_row( 'rest_disable', $current_settings );
	}

	if ( gravityview()->plugin->supports( \GV\Plugin::FEATURE_REST ) && ( gravityview()->plugin->settings->get( 'rest_api' ) !== '1' ) ) {
		GravityView_Render_Settings::render_setting_row( 'rest_enable', $current_settings );
	}

	/**
	 * @since 2.0
	 */
	GravityView_Render_Settings::render_setting_row( 'csv_enable', $current_settings );

	/**
	 * @since develop
	 */
	GravityView_Render_Settings::render_setting_row( 'csv_nolimit', $current_settings );

	do_action( 'gravityview_admin_directory_settings', $current_settings );

	?>
</table>
