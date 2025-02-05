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

GravityView_Render_Settings::render_setting_row( 'grid', $current_settings );
GravityView_Render_Settings::render_setting_row( 'grid_columns', $current_settings );
GravityView_Render_Settings::render_setting_row( 'grid_min_width', $current_settings );
GravityView_Render_Settings::render_setting_row( 'grid_gap', $current_settings );
GravityView_Render_Settings::render_setting_row( 'stylesheet', $current_settings );

?>
</table>
