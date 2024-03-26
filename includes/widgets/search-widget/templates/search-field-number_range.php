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

$min = $value['min'] ?? null; // Can't trust `rgar` here.
$max = $value['max'] ?? null;

$error = null;
if ( is_numeric( $min ) && is_numeric( $max ) && $min > $max ) {
	$error = esc_html__( 'The "from" value is lower than the "to" value.', 'gk-gravityview' );
}
?>

<div class="gv-search-box gv-search-number gv-search-number-range">
	<?php if ( ! gv_empty( $label, false, false ) ) { ?>
	<label for="search-box-<?php echo esc_attr( $name ) . '-start'; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<input name="<?php echo esc_attr( $name ) . '[min]'; ?>" id="search-box-<?php echo esc_attr( $name ) . '-min'; ?>" type="number" placeholder="<?php esc_attr_e( 'From', 'gk-gravityview' ); ?>" value="<?php echo esc_attr( $min ); ?>">
		<input name="<?php echo esc_attr( $name ) . '[max]'; ?>" id="search-box-<?php echo esc_attr( $name ) . '-max'; ?>" type="number" placeholder="<?php esc_attr_e( 'To', 'gk-gravityview' ); ?>" value="<?php echo esc_attr( $max ); ?>">
	</p>
	<?php if ( $error ) {
		printf( '<p class="error">%s</p>', $error );
	} ?>
</div>
