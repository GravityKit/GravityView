<?php
/**
 * Display the search RADIO input field
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$search_field = \GV\Utils::get( $data, 'search_field', [] );
$custom_class = \GV\Utils::get( $search_field, 'custom_class', '' );

// Make sure that there are choices to display
if ( empty( $search_field['choices'] ) ) {
	gravityview()->log->debug( 'search-field-radio.php - No choices for field' );
	return;
}

?>
<div class="gv-search-box gv-search-field-radio <?php echo esc_attr( $custom_class ); ?>">
	<?php if ( ! gv_empty( $search_field['label'], false, false ) ) { ?>
	<label for=search-box-<?php echo esc_attr( $search_field['name'] ); ?>><?php echo esc_html( $search_field['label'] ); ?></label>
	<?php } ?>
	<p>
	<?php foreach ( $search_field['choices'] as $choice ) { ?>

		<label for="search-box-<?php echo sanitize_html_class( $search_field['name'] . $choice['value'] . $choice['text'] ); ?>" class="gv-check-radio">
			<input type="radio" name="<?php echo esc_attr( $search_field['name'] ); ?>" value="<?php echo esc_attr( $choice['value'] ); ?>" id="search-box-<?php echo sanitize_html_class( $search_field['name'] . $choice['value'] . $choice['text'] ); ?>" <?php checked( $choice['value'], $search_field['value'], true ); ?>>
			<?php echo esc_html( $choice['text'] ); ?>
		</label>

	<?php } ?>
	</p>
</div>
