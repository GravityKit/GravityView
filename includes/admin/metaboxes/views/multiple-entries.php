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

	/**
	 * @since 1.5.4
	 */
	GravityView_Render_Settings::render_setting_row( 'hide_until_searched', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'hide_empty', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'no_results_text', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'no_search_results_text', $current_settings );

?>
</table>
