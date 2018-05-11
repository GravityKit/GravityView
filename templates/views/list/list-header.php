<?php
/**
 * The header for the output list.
 *
 * @global \GV\Template_Context $gravityview
 */
?>
<?php gravityview_before( $gravityview ); ?>
<div class="<?php gv_container_class( 'gv-list-container gv-list-view gv-list-multiple-container', true, $gravityview ); ?>">
	<?php gravityview_header( $gravityview ); ?>
