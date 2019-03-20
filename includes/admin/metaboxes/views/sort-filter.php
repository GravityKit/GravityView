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
<table class="form-table">

	<?php

	do_action( 'gravityview_metabox_sort_filter_before', $current_settings );

	// Begin Sort fields
	do_action( 'gravityview_metabox_sort_before', $current_settings );

	/**
	 * @since 1.7
	 */
	GravityView_Render_Settings::render_setting_row( 'sort_columns', $current_settings );

	$sort_fields_input = '<select name="template_settings[sort_field][]" id="gravityview_sort_field">%s</select>';

	if ( is_array( $current_settings['sort_field'] ) ) {
		$primary_sort_fields = gravityview_get_sortable_fields( $curr_form, $current_settings['sort_field'][0] );
		$secondary_sort_fields = gravityview_get_sortable_fields( $curr_form, $current_settings['sort_field'][1] );
	} else {
		$primary_sort_fields = $secondary_sort_fields = gravityview_get_sortable_fields( $curr_form, $current_settings['sort_field'] );
    }


	GravityView_Render_Settings::render_setting_row( 'sort_field', $current_settings, sprintf( $sort_fields_input, $primary_sort_fields ) );

	GravityView_Render_Settings::render_setting_row( 'sort_direction', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'sort_field_2', $current_settings, sprintf( $sort_fields_input, $secondary_sort_fields ) );


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