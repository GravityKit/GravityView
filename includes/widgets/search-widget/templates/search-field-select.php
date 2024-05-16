<?php
/**
 * Display the search SELECT input field
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$search_field = \GV\Utils::get( $data, 'search_field', [] );
$form_id = \GV\Utils::get( $search_field, 'form_id', null );
$form = \GV\GF_Form::by_id( $form_id );

// Make sure that there are choices to display
if ( empty( $search_field['choices'] ) ) {
	gravityview()->log->debug( 'search-field-select.php - No choices for field' );
	return;
}

if ( is_array( $search_field['value'] ) ) {
	gravityview()->log->debug( 'search-field-select.php - Array values passed; using first value.' );
	$search_field['value'] = reset( $search_field['value'] );
}

$gf_field = GFAPI::get_field( $form, $search_field['key'] );
$placeholder = \GV\Utils::get( $gf_field, 'placeholder', '' );

/**
 * @filter `gravityview/extension/search/select_default` Define the text for the default option in a select (multi or single dropdown)
 *
 * @since 1.16.4
 *
 * @param string $default_option Default: `&mdash;` (—)
 * @param string $field_type Field type: "select" or "multiselect"
 */
$default_option = apply_filters( 'gravityview/extension/search/select_default', ( '' === $placeholder ? '&mdash;' : $placeholder ), 'select' );

?>
<div class="gv-search-box gv-search-field-select">
	<?php if ( ! gv_empty( $search_field['label'], false, false ) ) { ?>
		<label for="search-box-<?php echo esc_attr( $search_field['name'] ); ?>"><?php echo esc_html( $search_field['label'] ); ?></label>
	<?php } ?>
	<p>
		<select name="<?php echo esc_attr( $search_field['name'] ); ?>" id="search-box-<?php echo esc_attr( $search_field['name'] ); ?>">
			<option value="" <?php gv_selected( '', $search_field['value'], true ); ?>><?php echo esc_html( $default_option ); ?></option>
			<?php
			foreach ( $search_field['choices'] as $choice ) {
				?>
				<?php if ( is_array( $choice['value'] ) ) { ?>
					<optgroup label="<?php echo esc_attr( $choice['text'] ); ?>">
						<?php foreach ( $choice['value'] as $subchoice ) : ?>
							<option value="<?php echo esc_attr( $subchoice['value'] ); ?>" <?php gv_selected( esc_attr( $subchoice['value'] ), esc_attr( $search_field['value'] ), true ); ?>><?php echo esc_html( $subchoice['text'] ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php } else { ?>
					<option value="<?php echo esc_attr( $choice['value'] ); ?>" <?php gv_selected( esc_attr( $choice['value'] ), esc_attr( $search_field['value'] ), true ); ?>><?php echo esc_html( $choice['text'] ); ?></option>
				<?php } ?>
			<?php } ?>
		</select>
	</p>
</div>
