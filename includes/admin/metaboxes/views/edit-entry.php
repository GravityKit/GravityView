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

/*Edit Entry
Redirect after update (Multiple, Single, URL) (new)
Back URL (Multiple, Single, URL) (new)*/

	GravityView_Render_Settings::render_setting_row( 'user_edit', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'unapprove_edit', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'user_delete', $current_settings );

	?>
</table>