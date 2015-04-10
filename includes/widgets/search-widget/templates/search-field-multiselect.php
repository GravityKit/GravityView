<?php
/**
 * Display the search MULTISELECT input field
 *
 * @see class-search-widget.php
 */

$gravityview_view = GravityView_View::getInstance();
$view_id = $gravityview_view->getViewId();
$search_field = $gravityview_view->search_field;

// Make sure that there are choices to display
if( empty( $search_field['choices'] ) ) {
	do_action('gravityview_log_debug', 'search-field-multiselect.php - No choices for field' );
	return;
}

?>
<div class="gv-search-box">
	<label for=search-box-<?php echo esc_attr( $search_field['name'] ); ?>>
		<?php echo esc_html( $search_field['label'] ); ?>
	</label>
	<p>
		<select name="<?php echo esc_attr( $search_field['name'] ); ?>[]" id="search-box-<?php echo esc_attr( $search_field['name'] ); ?>" multiple>
			<option value="" <?php gv_selected( '', $search_field['value'], true ); ?>>&mdash;</option>
			<?php
			foreach( $search_field['choices'] as $choice ) : ?>
				<option value="<?php echo esc_attr( $choice['value'] ); ?>" <?php gv_selected( $choice['value'], $search_field['value'], true ); ?>><?php echo esc_html( $choice['text'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
</div>