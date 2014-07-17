<?php
/**
 * Display the Search widget
 *
 * @see includes/default-widgets.php
 */

global $gravityview_view, $wp_rewrite;

$view_id = $gravityview_view->view_id;

?>

<form class="gv-widget-search <?php echo gravityview_sanitize_html_class( apply_filters('gravityview_search_class', 'gv-search-horizontal' ) ); ?>" method="get" action="">

	<div class="gv-search-box">

		<?php if( $gravityview_view->search_free ): ?>
			<div class="gv-search">
				<label for="gv_search_<?php echo $view_id; ?>"><?php esc_html_e( 'Search Entries:', 'gravity-view' ); ?></label>
				<p><input type="text" name="gv_search" id="gv_search_<?php echo $view_id; ?>" value="<?php echo $gravityview_view->curr_search; ?>" /></p>
			</div>
		<?php endif; ?>

	</div>

	<?php if( $gravityview_view->search_date ): ?>
	<div class="gv-search-box gv-search-date">
		<label for="gv_start_date_<?php echo $view_id; ?>"><?php esc_html_e('Filter by date:', 'gravity-view' ); ?></label>
		<p>
		<input name="gv_start" id="gv_start_date_<?php echo $view_id; ?>" type="text" class="<?php echo esc_html($gravityview_view->datepicker_class); ?>" placeholder="<?php esc_attr_e('Start date', 'gravity-view' ); ?>" value="<?php echo $gravityview_view->curr_start; ?>">
		<input name="gv_end" id="gv_end_date_<?php echo $view_id; ?>" type="text" class="<?php echo  esc_html($gravityview_view->datepicker_class); ?>" placeholder="<?php esc_attr_e('End date', 'gravity-view' ); ?>" value="<?php echo $gravityview_view->curr_end; ?>">
		</p>
	</div>
	<?php endif; ?>

	<?php
		// search filters (fields)
		echo $gravityview_view->search_fields;
	?>

	<div class="gv-search-box gv-search-box-submit">
		<?php

		// Support default permalink structure
		if( !empty( $_GET['gravityview'] ) && false === $wp_rewrite->using_index_permalinks() ) {
			echo '<input type="hidden" name="gravityview" value="'.esc_attr( $_GET['gravityview'] ).'" />';
		}
		?>
		<input type="submit" class="button gv-search-button" id="gv_search_button_<?php echo $view_id; ?>" value="<?php esc_attr_e( 'Search', 'gravity-view' ); ?>" />
	</div>
</form>
