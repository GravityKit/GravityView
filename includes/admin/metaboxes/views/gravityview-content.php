<?php
/**
 * Display the tab panels for the Settings metabox
 *
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/views
 * @since 1.8
 *
 * @global array $metaboxes
 * @global WP_Post $post
 */

?>

<div id="gravityview-metabox-content-container">
<?php

	/**
	 * Loop through the array of registered metaboxes
	 * @var GravityView_Metabox_Tab $metabox
	 */
	foreach( $metaboxes as $metabox ) {

		echo '<div id="'.esc_attr( $metabox->id ).'">';

		$metabox->render( $post );

		echo '</div>';
	}
?>
</div>