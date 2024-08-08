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
	 *
	 * @var GravityView_Metabox_Tab $metabox
	 */
foreach ( $metaboxes as $metabox ) {

	echo '<div id="' . esc_attr( $metabox->id ) . '">';

	/**
	 * Can be used to insert additional HTML inside the <div> before the metabox is rendered.
	 *
	 * @since  2.26
	 *
	 * @action `gk/gravityview/metabox/content/before`
	 *
	 * @param GravityView_Metabox_Tab $metabox Current GravityView_Metabox_Tab object.
	 */
	do_action( 'gk/gravityview/metabox/content/before', $metabox );

	$metabox->render( $post );

	/**
	 * Can be used to insert additional HTML inside the <div> after the metabox is rendered.
	 *
	 * @since  2.26
	 *
	 * @action `gk/gravityview/metabox/content/after`
	 *
	 * @param GravityView_Metabox_Tab $metabox Current GravityView_Metabox_Tab object.
	 */
	do_action( 'gk/gravityview/metabox/content/after', $metabox );

	echo '</div>';
}
?>
</div>
