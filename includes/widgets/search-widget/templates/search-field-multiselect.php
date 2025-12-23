<?php
/**
 * Display the search MULTISELECT input field
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$search_field = \GV\Utils::get( $data, 'search_field', [] );
$custom_class = \GV\Utils::get( $search_field, 'custom_class', '' );
$form_id      = \GV\Utils::get( $search_field, 'form_id', null );
$form         = \GV\GF_Form::by_id( $form_id );

// Make sure that there are choices to display
if ( empty( $search_field['choices'] ) ) {
	gravityview()->log->debug( 'search-field-multiselect.php - No choices for field' );
	return;
}

$gf_field = GFAPI::get_field( $form, $search_field['key'] );
$placeholder = \GV\Utils::get( $gf_field, 'placeholder', '' );

/**
 * Defines the text for the default option in a select (multi or single dropdown).
 *
 * @since 1.16.4
 *
 * @param string $default_option Default: `&mdash;` (—).
 * @param string $field_type     Field type: "select" or "multiselect".
 */
$default_option = apply_filters( 'gravityview/extension/search/select_default', ( '' === $placeholder ? '&mdash;' : $placeholder ), 'multiselect' );

?>
<div class="gv-search-box gv-search-field-multiselect <?php echo $custom_class; ?>">
	<?php if ( ! gv_empty( $search_field['label'], false, false ) ) { ?>
		<label for="search-box-<?php echo esc_attr( $search_field['name'] ); ?>">
			<?php echo esc_html( $search_field['label'] ); ?>
			<?php if ( ! empty( $search_field['required'] ) ) { ?>
				<span class="gv-required-indicator">*</span>
			<?php } ?>
		</label>
	<?php } ?>
	<p>
		<select name="<?php echo esc_attr( $search_field['name'] ); ?>[]" id="search-box-<?php echo esc_attr( $search_field['name'] ); ?>" multiple<?php if ( ! empty( $search_field['required'] ) ) { ?> required aria-required="true" data-required-message="<?php echo esc_attr( $search_field['required_message'] ?? __( 'This field is required.', 'gk-gravityview' ) ); ?>"<?php } ?>>
			<option value="" <?php gv_selected( '', $search_field['value'], true ); ?>><?php echo esc_html( $default_option ); ?></option>
			<?php
			foreach ( $search_field['choices'] as $choice ) :
				?>
				<option value="<?php echo esc_attr( $choice['value'] ); ?>" <?php gv_selected( $choice['value'], $search_field['value'], true ); ?>><?php echo esc_html( $choice['text'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
</div>
