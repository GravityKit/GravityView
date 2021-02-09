<?php
/**
 * Display the search CHECKBOX input field (supports multi-select)
 *
 * @file class-search-widget.php See for usage
 */

$gravityview_view = GravityView_View::getInstance();
$view_id = $gravityview_view->getViewId();
$search_field = $gravityview_view->search_field;

// Make sure that there are choices to display
if( empty( $search_field['choices'] ) ) {
	gravityview()->log->debug( 'search-field-checkbox.php - No choices for field' );
	return;
}

?>
<div class="gv-search-box gv-search-field-checkbox">
	<?php if( ! gv_empty( $search_field['label'], false, false ) ) { ?>
	<label for=search-box-<?php echo esc_attr( $search_field['name'] ); ?>><?php echo esc_html( $search_field['label'] ); ?></label>
	<?php } ?>
	<p>
	<?php foreach( $search_field['choices'] as $choice ) { ?>

		<label for="search-box-<?php echo sanitize_html_class( $search_field['name'].$choice['value'].$choice['text'] ); ?>" class="gv-check-radio">
			<input type="checkbox" name="<?php echo esc_attr( $search_field['name'] ); ?>[]" value="<?php echo esc_attr( $choice['value'] ); ?>" id="search-box-<?php echo sanitize_html_class( $search_field['name'].$choice['value'].$choice['text'] ); ?>" <?php gv_selected( $choice['value'], $search_field['value'], true, 'checked' ); ?>>
			<?php echo esc_html( $choice['text'] ); ?>
		</label>

	<?php } ?>
	</p>
</div>