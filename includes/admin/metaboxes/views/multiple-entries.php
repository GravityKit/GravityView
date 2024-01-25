<?php
/**
 * @package    GravityView
 * @subpackage Gravityview/admin/metaboxes/views
 * @global $post
 */
global $post;

$curr_form = gravityview_get_form_id( $post->ID );

// View template settings
$current_settings = gravityview_get_template_settings( $post->ID );

$settings = array(
	'page_size',
	'hide_until_searched',
	'hide_empty',
	'no_entries_options',
	'no_results_text',
	'no_entries_form',
	'no_entries_form_title',
	'no_entries_form_description',
	'no_entries_redirect',
	'no_search_results_text',
);

?>

<table class="form-table">
	<?php

	foreach ( $settings as $setting ) {
		GravityView_Render_Settings::render_setting_row( $setting, $current_settings );
	}

	/**
	 * Render additional Multiple Entries settings
	 *
	 * @since 2.9.4
	 *
	 * @param array $current_settings Array of settings returned from {@see gravityview_get_template_settings()}.
	 */
	do_action( 'gravityview/metaboxes/multiple_entries/after', $current_settings );
	?>
</table>
