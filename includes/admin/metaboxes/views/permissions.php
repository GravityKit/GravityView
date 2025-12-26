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

<table class="form-table striped">
<?php

	/**
	 * Render additional Permissions metabox settings.
	 *
	 * @since 2.9
	 *
	 * @param array $current_settings Array of View settings.
	 */
	do_action( 'gravityview/metaboxes/permissions_before', $current_settings );

	/**
	 * @since 2.21
	 */
	GravityView_Render_Settings::render_setting_row( 'is_secure', $current_settings );

	/**
	 * @since 1.15.2
	 */
	GravityView_Render_Settings::render_setting_row( 'embed_only', $current_settings );

	/**
	 * @since  2.5
	 */
	GravityView_Render_Settings::render_setting_row( 'user_duplicate', $current_settings );

	/**
	 * @since 2.0
	 */
if ( gravityview()->plugin->supports( \GV\Plugin::FEATURE_REST ) && ( gravityview()->plugin->settings->get( 'rest_api' ) ) ) {
	GravityView_Render_Settings::render_setting_row( 'rest_disable', $current_settings );
}

if ( gravityview()->plugin->supports( \GV\Plugin::FEATURE_REST ) && ( ! gravityview()->plugin->settings->get( 'rest_api' ) ) ) {
	GravityView_Render_Settings::render_setting_row( 'rest_enable', $current_settings );
}

	/**
	 * @since 2.0
	 */
	GravityView_Render_Settings::render_setting_row( 'csv_enable', $current_settings );

	/**
	 * @since 2.4
	 */
	GravityView_Render_Settings::render_setting_row( 'csv_nolimit', $current_settings );

	/**
	 * Render additional Permissions metabox settings, like Delete Entry (if available).
	 *
	 * @since 2.9
	 *
	 * @param array $current_settings Array of View settings.
	 */
	do_action( 'gravityview/metaboxes/permissions_after', $current_settings );
?>
</table>
