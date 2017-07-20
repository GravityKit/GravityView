<?php
/**
 * Display the search SELECT input field
 *
 * @see class-search-widget.php
 */

$gravityview_view = GravityView_View::getInstance();
$view_id = $gravityview_view->getViewId();
$search_field = $gravityview_view->search_field;

// Make sure that there are choices to display
if( empty( $search_field['choices'] ) ) {
	do_action('gravityview_log_debug', 'search-field-select.php - No choices for field' );
	return;
}

/**
 * @filter `gravityview/extension/search/select_default` Define the text for the default option in a select (multi or single dropdown)
 * @since 1.16.4
 * @param string $default_option Default: `&mdash;` (—)
 * @param string $field_type Field type: "select" or "multiselect"
 */
$default_option = apply_filters('gravityview/extension/search/select_default', '&mdash;', 'select' );

?>
<div class="gv-search-box gv-search-field-select">
	<?php if( ! gv_empty( $search_field['label'], false, false ) ) { ?>
		<label for="search-box-<?php echo esc_attr( $search_field['name'] ); ?>"><?php echo esc_html( $search_field['label'] ); ?></label>
	<?php } ?>
	<p>
		<select name="<?php echo esc_attr( $search_field['name'] ); ?>" id="search-box-<?php echo esc_attr( $search_field['name'] ); ?>">
			<option value="" <?php gv_selected( '', $search_field['value'], true ); ?>><?php echo esc_html( $default_option ); ?></option>
			<?php
			foreach( $search_field['choices'] as $choice ) : ?>
				<option value="<?php echo esc_attr( $choice['value'] ); ?>" <?php gv_selected( esc_attr( $choice['value'] ), esc_attr( $search_field['value'] ), true ); ?>><?php echo esc_html( $choice['text'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
</div>