<?php
/**
 * Display the search by entry date input boxes
 *
 * @see class-search-widget.php
 */

global $gravityview_view;
$view_id = $gravityview_view->view_id;
$value = $gravityview_view->search_field['value'];

?>

<div class="gv-search-box gv-search-date">
	<label for="gv_start_date_<?php echo $view_id; ?>"><?php esc_html_e('Filter by date:', 'gravityview' ); ?></label>
	<p>
		<input name="gv_start" id="gv_start_date_<?php echo $view_id; ?>" type="text" class="<?php echo esc_html( $gravityview_view->datepicker_class ); ?>" placeholder="<?php esc_attr_e('Start date', 'gravityview' ); ?>" value="<?php echo $value['start']; ?>">
		<input name="gv_end" id="gv_end_date_<?php echo $view_id; ?>" type="text" class="<?php echo esc_html( $gravityview_view->datepicker_class ); ?>" placeholder="<?php esc_attr_e('End date', 'gravityview' ); ?>" value="<?php echo $value['end']; ?>">
	</p>
</div>