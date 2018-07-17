<?php
/**
 * @since 2.0
 */

$gravityview_view = GravityView_View::getInstance();
$view_id = $gravityview_view->getViewId();
?>
<div class="gv-search-box gv-search-box-submit">
	<?php

	// Output the Clear button, if enabled
	GravityView_Widget_Search::the_clear_search_button();

	?>
	<input type="hidden" name="mode" value="<?php echo esc_attr( $gravityview_view->search_mode ); ?>" />
	<input type="submit" class="button gv-search-button" id="gv_search_button_<?php echo $view_id; ?>" value="<?php esc_attr_e( 'Search', 'gravityview' ); ?>" />
</div>
