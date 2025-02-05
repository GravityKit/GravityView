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
$name             = $gravityview_view->search_field['name'];
?>

<div class="gv-search-box gv-search-date gv-search-date-range">
	<?php if ( ! gv_empty( $label, false, false ) ) { ?>
		<label for="search-box-<?php echo esc_attr( $name ) . '-start'; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<input name="<?php echo esc_attr( $name ) . '[start]'; ?>" id="search-box-<?php echo esc_attr( $name ) . '-start'; ?>" type="text" class="<?php echo esc_html( $gravityview_view->atts['datepicker_class'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Start date', 'gk-gravityview' ); ?>" value="<?php echo esc_attr( $value['start'] ?? '' ); ?>">
		<input name="<?php echo esc_attr( $name ) . '[end]'; ?>" id="search-box-<?php echo esc_attr( $name ) . '-end'; ?>" type="text" class="<?php echo esc_html( $gravityview_view->atts['datepicker_class'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'End date', 'gk-gravityview' ); ?>" value="<?php echo esc_attr( $value['end'] ?? '' );; ?>">
	</p>
</div>