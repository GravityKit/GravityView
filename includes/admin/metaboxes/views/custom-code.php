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

	/**
	 * @since 1.15.2
	 */
	GravityView_Render_Settings::render_setting_row( 'custom_css', $current_settings );

	/**
	 * @since  2.5
	 */
	GravityView_Render_Settings::render_setting_row( 'custom_javascript', $current_settings );

	/**
	 * @since 2.19
	 */
	GravityView_Render_Settings::render_setting_row( 'embed_js_in_footer', $current_settings );
?>
</table>
