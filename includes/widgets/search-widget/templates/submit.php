<?php
/**
 * @since 2.0
 *
 * @global \GV\Template_Context $gravityview
 * @global \GV\Widget           $widget
 * @global \GV\Template         $template
 * @global array                $search_field
 */

?>
<div class="gv-search-box gv-search-box-submit">
	<?php

	// Output the Clear button, if enabled
	if ( $widget->search_clear ) {
		$url = strtok( add_query_arg( array() ), '?' );
		echo gravityview_get_link( $url, esc_html__( 'Clear', 'gravityview' ), 'class=button gv-search-clear' );
	}

	?>
	<input type="hidden" name="mode" value="<?php echo esc_attr( $widget->search_mode ); ?>" />
	<input type="submit" class="button gv-search-button" id="gv_search_button_<?php echo $gravityview->view->ID; ?>" value="<?php esc_attr_e( 'Search', 'gravityview' ); ?>" />
</div>
