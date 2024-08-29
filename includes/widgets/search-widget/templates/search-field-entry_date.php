<?php
/**
 * Display the search by entry date input boxes
 *
 * @file class-search-widget.php See for usage
 */

$gravityview_view = GravityView_View::getInstance();
$view_id          = $gravityview_view->getViewId();
$value            = $gravityview_view->search_field['value'];
$label            = $gravityview_view->search_field['label'];

?>

<div class="gv-search-box gv-search-date gv-search-date-range gv-search-field-entry_date">
	<?php if ( ! gv_empty( $label, false, false ) ) { ?>
	<label for="gv_start_date_<?php echo $view_id; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<input name="gv_start" id="gv_start_date_<?php echo $view_id; ?>" type="text" class="<?php echo esc_attr( $gravityview_view->atts['datepicker_class'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Start date', 'gk-gravityview' ); ?>" value="<?php echo $value['start']; ?>">
		<input name="gv_end" id="gv_end_date_<?php echo $view_id; ?>" type="text" class="<?php echo esc_attr( $gravityview_view->atts['datepicker_class'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'End date', 'gk-gravityview' ); ?>" value="<?php echo $value['end']; ?>">
	</p>
</div>