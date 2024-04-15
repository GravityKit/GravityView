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

$is_currency = 'total' === $gravityview_view->search_field['type'];
if ( ! $is_currency ) {
	// could still be currency from the field.
	$field       = GVCommon::get_field( $gravityview_view->getForm() ?? [], $gravityview_view->search_field['key'] );
	$is_currency = 'currency' === $field->numberFormat;
}

/**
 * Modify the step value for the input fields.
 *
 * @filter gk/gravityview/search/number-range/step
 *
 * @since  2.22
 *
 * @param string           $value            The step size.
 * @param GravityView_View $gravityview_view The view object.
 */
$step = apply_filters(
	'gk/gravityview/search/number-range/step',
	'quantity' === $gravityview_view->search_field['type'] ? '1' : 'any',
	$gravityview_view
);
?>

<div class="gv-search-box gv-search-number gv-search-number-range">
	<?php if ( ! gv_empty( $label, false, false ) ) { ?>
		<label for="search-box-<?php echo esc_attr( $name ) . '-start'; ?>">
			<?php echo esc_html( $label ) . ( $is_currency ? ' (' . GFCommon::get_currency() . ')' : '' ); ?>
		</label>
	<?php } ?>
	<p>
		<input name="<?php echo esc_attr( $name ) . '[min]'; ?>"
			   id="search-box-<?php echo esc_attr( $name ) . '-min'; ?>"
			   type="number"
			   placeholder="<?php esc_attr_e( 'From', 'gk-gravityview' ); ?>"
			   step="<?php echo esc_attr( $step ); ?>"
			   value="<?php echo esc_attr( $min ); ?>">

		<input name="<?php echo esc_attr( $name ) . '[max]'; ?>"
			   id="search-box-<?php echo esc_attr( $name ) . '-max'; ?>"
			   type="number"
			   placeholder="<?php esc_attr_e( 'To', 'gk-gravityview' ); ?>"
			   step="<?php echo esc_attr( $step ); ?>"
			   value="<?php echo esc_attr( $max ); ?>">
	</p>
	<?php if ( $error ) {
		printf( '<p class="error">%s</p>', $error );
	} ?>
</div>
