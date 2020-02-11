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

	GravityView_Render_Settings::render_setting_row( 'lightbox', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'show_only_approved', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'admin_show_all_statuses', $current_settings );

	do_action( 'gravityview_admin_directory_settings', $current_settings );
?>
</table>
