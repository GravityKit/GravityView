<?php
/**
 * Display the search by entry date input boxes
 *
 * @see class-search-widget.php
 */

$gravityview_view = GravityView_View::getInstance();
$view_id = $gravityview_view->getViewId();
$value = $gravityview_view->search_field['value'];
$label = $gravityview_view->search_field['label'];
$name = $gravityview_view->search_field['name'];
?>

<div class="gv-search-box gv-search-date gv-search-date-range">
	<?php if( ! gv_empty( $label, false, false ) ) { ?>
	<label for="search-box-<?php echo esc_attr( $name ).'-start'; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<input name="<?php echo esc_attr( $name ).'[start]'; ?>" id="search-box-<?php echo esc_attr( $name ).'-start'; ?>" type="text" class="<?php echo esc_html( $gravityview_view->datepicker_class ); ?>" placeholder="<?php esc_attr_e('Start date', 'gravityview' ); ?>" value="<?php echo $value['start']; ?>">
		<input name="<?php echo esc_attr( $name ).'[end]'; ?>" id="search-box-<?php echo esc_attr( $name ).'-end'; ?>" type="text" class="<?php echo esc_html( $gravityview_view->datepicker_class ); ?>" placeholder="<?php esc_attr_e('End date', 'gravityview' ); ?>" value="<?php echo $value['end']; ?>">
	</p>
</div>