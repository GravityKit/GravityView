<?php
/**
 * Display the search CHECKBOX input field (supports multi-select)
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$search_field = \GV\Utils::get( $data, 'search_field', [] );
$custom_class = \GV\Utils::get( $search_field, 'custom_class', '' );

// Make sure that there are choices to display
if ( empty( $search_field['choices'] ) ) {
	gravityview()->log->debug( 'search-field-checkbox.php - No choices for field' );
	return;
}

?>
<div class="gv-search-box gv-search-field-checkbox <?php echo $custom_class; ?>"<?php if ( ! empty( $search_field['required'] ) ) { ?> data-gv-required-type="checkbox" data-required-message="<?php echo '' !== ( $search_field['required_message'] ?? '' ) ? esc_attr( $search_field['required_message'] ) : esc_attr__( 'Please select at least one option.', 'gk-gravityview' ); ?>"<?php } ?>>
	<?php if ( ! gv_empty( $search_field['label'], false, false ) ) { ?>
	<label for="search-box-<?php echo esc_attr( $search_field['name'] ); ?>">
		<?php echo esc_html( $search_field['label'] ); ?>
		<?php if ( ! empty( $search_field['required'] ) ) { ?>
			<span class="gv-required-indicator">*</span>
		<?php } ?>
	</label>
	<?php } ?>
	<p>
	<?php foreach ( $search_field['choices'] as $choice ) { ?>

		<label for="search-box-<?php echo sanitize_html_class( $search_field['name'] . $choice['value'] . $choice['text'] ); ?>" class="gv-check-radio">
			<input type="checkbox" name="<?php echo esc_attr( $search_field['name'] ); ?>[]" value="<?php echo esc_attr( $choice['value'] ); ?>" id="search-box-<?php echo sanitize_html_class( $search_field['name'] . $choice['value'] . $choice['text'] ); ?>" <?php gv_selected( $choice['value'], $search_field['value'], true, 'checked' ); ?>>
			<?php echo esc_html( $choice['text'] ); ?>
		</label>

	<?php } ?>
	</p>
</div>
