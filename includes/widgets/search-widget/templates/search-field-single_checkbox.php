<?php
/**
 * Display the search single CHECKBOX input field ( on/off type)
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$search_field = \GV\Utils::get( $data, 'search_field', [] );
$custom_class = \GV\Utils::get( $search_field, 'custom_class', '' );
?>

<div class="gv-search-box gv-search-field-single_checkbox <?php echo $custom_class; ?>">
	<label for="search-box-<?php echo esc_attr( $search_field['name'] ); ?>" class="gv-check-radio">
		<input type="checkbox" name="<?php echo esc_attr( $search_field['name'] ); ?>" value="1" id="search-box-<?php echo esc_attr( $search_field['name'] ); ?>" <?php checked( '1', $search_field['value'], true ); ?><?php if ( ! empty( $search_field['required'] ) ) { ?> required aria-required="true" data-required-message="<?php echo esc_attr( $search_field['required_message'] ?? __( 'This field is required.', 'gk-gravityview' ) ); ?>"<?php } ?>>
			<?php
			if ( ! gv_empty( $search_field['label'], false, false ) ) {
				echo esc_html( $search_field['label'] );
				if ( ! empty( $search_field['required'] ) ) { ?>
					<span class="gv-required-indicator">*</span>
				<?php }
			}
			?>
	</label>
</div>
