<?php
/**
 * Display the search by numeric range.
 *
 * @file class-search-widget.php See for usage
 */

$gravityview_view = GravityView_View::getInstance();
$view_id          = $gravityview_view->getViewId();
$value            = $gravityview_view->search_field['value'];
$label            = $gravityview_view->search_field['label'];
$name             = $gravityview_view->search_field['name'];
?>

<div class="gv-search-box gv-search-number gv-search-number-range">
	<?php if ( ! gv_empty( $label, false, false ) ) { ?>
	<label for="search-box-<?php echo esc_attr( $name ) . '-start'; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<input name="<?php echo esc_attr( $name ) . '[min]'; ?>" id="search-box-<?php echo esc_attr( $name ) . '-min'; ?>" type="number" placeholder="<?php esc_attr_e( 'From', 'gk-gravityview' ); ?>" value="<?php echo esc_attr( $value['min'] ); ?>">
		<input name="<?php echo esc_attr( $name ) . '[max]'; ?>" id="search-box-<?php echo esc_attr( $name ) . '-max'; ?>" type="number" placeholder="<?php esc_attr_e( 'To', 'gk-gravityview' ); ?>" value="<?php echo esc_attr( $value['max'] ); ?>">
	</p>
</div>
