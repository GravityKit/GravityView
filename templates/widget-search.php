<?php
/**
 * Display the Search widget
 *
 * @see includes/default-widgets.php
 */

global $gravityview_view;

?>

<form class="gv-widget-search" method="get" action="">

	<?php
		// search filters (fields)
		echo $gravityview_view->__get('search_fields');
	?>


	<div class="search-box">

		<?php if( $gravityview_view->__get('search_free') ): ?>
			<div class="gv-search">
				<label for="gv_search"><?php esc_html_e( 'Search Entries:', 'gravity-view' ); ?></label>
				<input type="text" name="gv_search" id="gv_search" value="<?php echo $gravityview_view->__get('curr_search'); ?>" />
			</div>
		<?php endif; ?>

		<?php if( $gravityview_view->__get('search_date') ): ?>

			<label for="gv_start_date"><?php esc_html_e('Filter by date:', 'gravity-view' ); ?></label>
			<input name="gv_start" id="gv_start_date" type="text" class="<?php echo esc_html($gravityview_view->__get('datepicker_class')); ?>" placeholder="<?php esc_attr_e('Start date', 'gravity-view' ); ?>" value="<?php echo $gravityview_view->__get('curr_start'); ?>">
			<input name="gv_end" id="gv_end_date" type="text" class="<?php echo  esc_html($gravityview_view->__get('datepicker_class')); ?>" placeholder="<?php esc_attr_e('End date', 'gravity-view' ); ?>" value="<?php echo $gravityview_view->__get('curr_end'); ?>">

		<?php endif; ?>
		<input type="submit" class="button" id="gv_search_button" value="<?php esc_attr_e( 'Search', 'gravity-view' ); ?>" />
	</div>
</form>