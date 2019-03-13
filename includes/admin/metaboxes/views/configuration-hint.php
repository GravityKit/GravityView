<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/partials
 * @global $post
 */

global $post;

$view = \GV\View::from_post( $post );
$view_fields = $view->fields->all();

$single_links = array_filter( wp_list_pluck( $view_fields, 'show_as_link' ) );
$has_single_link = ! empty( $single_links );

$single_fields = $view->fields->by_position( 'single_*' );
$has_single_fields = $single_fields->count() > 0;

$edit_fields = $view->fields->by_position( 'edit_*' );
$has_edit_fields = $edit_fields->count() > 0;

$edit_links = wp_list_filter( $view_fields, array( 'type' => 'edit_link' ) );
$has_edit_link = ! empty( $edit_links );
?>
<div class="misc-pub-section gv-configuration-hint misc-pub-section-last">
	<i class="dashicons dashicons-editor-code"></i>
	<span><?php esc_html_e( 'View Configuration', 'gravityview' ); ?></span>
	<ul>
		<li id="gv-config-single-links"><i class="dashicons <?php echo $has_single_link ? 'dashicons-yes' : 'dashicons-no'; ?>"></i> Fields link Single Entry</li>
		<li id="gv-config-single-fields"><i class="dashicons <?php echo $has_single_fields ? 'dashicons-yes' : 'dashicons-no'; ?>"></i> Single Entry is configured</li>
		<li id="gv-config-fields-edit"><i class="dashicons <?php echo $has_edit_link ? 'dashicons-yes' : 'dashicons-no'; ?>"></i> Link to Edit Entry</li>
		<li id="gv-config-fields-edit"><i class="dashicons <?php echo $has_edit_fields ? 'dashicons-no' : 'dashicons-yes'; ?>"></i> Edit Entry showing all fields</li>
	</ul>
</div>