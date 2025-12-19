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

<table class="form-table">
<?php

	GravityView_Render_Settings::render_setting_row( 'single_title', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'back_link_behavior', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'back_link_label', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'hide_empty_single', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'mark_entry_as_read', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'single_entry_slug', $current_settings );

?>
</table>
