<?php
/**
 * Display the search Entry ID input box
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$view_id      = \GV\Utils::get( $data, 'view_id', null );
$search_field = \GV\Utils::get( $data, 'search_field', null );
$value        = \GV\Utils::get( $search_field, 'value' );
$label        = \GV\Utils::get( $search_field, 'label' );
$custom_class = \GV\Utils::get( $search_field, 'custom_class', '' );
?>

<div class="gv-search-box gv-search-field-entry_id <?php echo $custom_class; ?>">
	<div class="gv-search">
		<?php if ( ! gv_empty( $label, false, false ) ) { ?>
		<label for="gv_entry_id_<?php echo (int) $view_id; ?>">
			<?php echo esc_html( $label ); ?>
			<?php if ( ! empty( $search_field['required'] ) ) { ?>
				<span class="gv-required-indicator">*</span>
			<?php } ?>
		</label>
		<?php } ?>
		<p><input type="text" name="gv_id" id="gv_entry_id_<?php echo (int) $view_id; ?>" value="<?php echo esc_attr( $value ); ?>"<?php if ( ! empty( $search_field['required'] ) ) { ?> required aria-required="true" data-required-message="<?php echo esc_attr( $search_field['required_message'] ?? __( 'This field is required.', 'gk-gravityview' ) ); ?>"<?php } ?> /></p>
	</div>
</div>
