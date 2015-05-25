<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/partials
 * @global WP_Post $post
 */
global $post;

$curr_form = gravityview_get_form_id( $post->ID );

// View template settings
$current_settings = gravityview_get_template_settings( $post->ID );

?>
<table class="form-table striped">

	<?php

	do_action( 'gravityview_metabox_sort_filter_before', $current_settings );

	// Begin Sort fields
	do_action( 'gravityview_metabox_sort_before', $current_settings );

	/**
	 * @since 1.7
	 */
	GravityView_Render_Settings::render_setting_row( 'sort_columns', $current_settings );

	$sort_fields_input = '<select name="template_settings[sort_field]" id="gravityview_sort_field">'.gravityview_get_sortable_fields( $curr_form, $current_settings['sort_field'] ).'</select>';

	GravityView_Render_Settings::render_setting_row( 'sort_field', $current_settings, $sort_fields_input );

	GravityView_Render_Settings::render_setting_row( 'sort_direction', $current_settings );


	// End Sort fields
	do_action( 'gravityview_metabox_sort_after', $current_settings );

	// Begin Filter fields
	do_action( 'gravityview_metabox_filter_before', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'start_date', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'end_date', $current_settings );

	// End Filter fields
	do_action( 'gravityview_metabox_filter_after', $current_settings );

	do_action( 'gravityview_metabox_sort_filter_after', $current_settings );

	?>

</table>