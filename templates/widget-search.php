<?php
/**
 * Display the Search widget
 *
 * @see includes/default-widgets.php
 */

global $gravityview_view;

$view_id = $gravityview_view->view_id;

?>

<form class="gv-widget-search" method="get" action="">

	<div class="search-box">

		<?php if( $gravityview_view->__get('search_free') ): ?>
			<div class="gv-search">
				<label for="gv_search_<?php echo $view_id; ?>"><?php esc_html_e( 'Search Entries:', 'gravity-view' ); ?></label>
				<p><input type="text" name="gv_search" id="gv_search_<?php echo $view_id; ?>" value="<?php echo $gravityview_view->__get('curr_search'); ?>" /></p>
			</div>
		<?php endif; ?>

		<?php if( $gravityview_view->__get('search_date') ): ?>
			<div class="gv-search-date">
				<label for="gv_start_date_<?php echo $view_id; ?>"><?php esc_html_e('Filter by date:', 'gravity-view' ); ?></label>
				<p>
				<input name="gv_start" id="gv_start_date_<?php echo $view_id; ?>" type="text" class="<?php echo esc_html($gravityview_view->__get('datepicker_class')); ?>" placeholder="<?php esc_attr_e('Start date', 'gravity-view' ); ?>" value="<?php echo $gravityview_view->__get('curr_start'); ?>">
				<input name="gv_end" id="gv_end_date_<?php echo $view_id; ?>" type="text" class="<?php echo  esc_html($gravityview_view->__get('datepicker_class')); ?>" placeholder="<?php esc_attr_e('End date', 'gravity-view' ); ?>" value="<?php echo $gravityview_view->__get('curr_end'); ?>">
				</p>
			</div>
		<?php endif; ?>
	</div>

	<?php
		// search filters (fields)
		echo $gravityview_view->__get('search_fields');
	?>

	<div>
		<p><input type="submit" class="button gv_search_button" id="gv_search_button_<?php echo $view_id; ?>" value="<?php esc_attr_e( 'Search', 'gravity-view' ); ?>" /></p>
	</div>
</form>
