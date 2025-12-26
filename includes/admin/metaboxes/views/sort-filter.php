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

	/**
	 * Fires at the beginning of the Sort & Filter metabox.
	 *
	 * @since 1.1.2
	 *
	 * @param array $current_settings Array of View settings.
	 */
	do_action( 'gravityview_metabox_sort_filter_before', $current_settings );

	// Begin Sort fields

	/**
	 * Fires before the sort settings are rendered.
	 *
	 * @since 1.1.2
	 *
	 * @param array $current_settings Array of View settings.
	 */
	do_action( 'gravityview_metabox_sort_before', $current_settings );

	/**
	 * @since 1.7
	 */
	GravityView_Render_Settings::render_setting_row( 'sort_columns', $current_settings );

	$sort_fields_input = '<select name="template_settings[sort_field][]" class="gravityview_sort_field" id="gravityview_sort_field_%d">%s</select>';

	if ( is_array( $current_settings['sort_field'] ) ) {
		$primary_sort_fields   = gravityview_get_sortable_fields( $curr_form, $current_settings['sort_field'][0] );
		$secondary_sort_fields = gravityview_get_sortable_fields( $curr_form, $current_settings['sort_field'][1] );
	} else {
		$primary_sort_fields = $secondary_sort_fields = gravityview_get_sortable_fields( $curr_form, $current_settings['sort_field'] );
	}

	// Splice the sort direction
	$_directions = array();
	foreach ( (array) \GV\Utils::get( $current_settings, 'sort_direction', array() ) as $i => $direction ) {
		if ( ! $i ) {
			$_directions['sort_direction'] = $direction;
		} else {
			$_directions[ sprintf( 'sort_direction_%d', $i + 1 ) ] = $direction;
		}
	}
	$current_settings = array_merge( $current_settings, $_directions );

	$sort_directions_input = '<select name="template_settings[sort_direction][]" class="gravityview_sort_direction" id="gravityview_sort_direction_%d">%s</select>';

	GravityView_Render_Settings::render_setting_row( 'sort_field', $current_settings, sprintf( $sort_fields_input, 1, $primary_sort_fields ) );

	GravityView_Render_Settings::render_setting_row( 'sort_direction', $current_settings, null, 'template_settings[sort_direction][]' );

	GravityView_Render_Settings::render_setting_row( 'sort_field_2', $current_settings, sprintf( $sort_fields_input, 2, $secondary_sort_fields ) );

	GravityView_Render_Settings::render_setting_row( 'sort_direction_2', $current_settings, null, 'template_settings[sort_direction][]' );


	// End Sort fields

	/**
	 * Fires after the sort settings are rendered.
	 *
	 * @since 1.1.2
	 *
	 * @param array $current_settings Array of View settings.
	 */
	do_action( 'gravityview_metabox_sort_after', $current_settings );

	// Begin Filter fields

	/**
	 * Fires before the filter settings are rendered.
	 *
	 * @since 1.1.2
	 *
	 * @param array $current_settings Array of View settings.
	 */
	do_action( 'gravityview_metabox_filter_before', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'start_date', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'end_date', $current_settings );

	// End Filter fields

	/**
	 * Fires after the filter settings are rendered.
	 *
	 * @since 1.1.2
	 *
	 * @param array $current_settings Array of View settings.
	 */
	do_action( 'gravityview_metabox_filter_after', $current_settings );

	/**
	 * Fires at the end of the Sort & Filter metabox.
	 *
	 * @since 1.1.2
	 *
	 * @param array $current_settings Array of View settings.
	 */
	do_action( 'gravityview_metabox_sort_filter_after', $current_settings );

	?>

</table>