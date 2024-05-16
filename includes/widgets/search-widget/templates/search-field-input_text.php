<?php
/**
 * Display the search text input field
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$search_field     = \GV\Utils::get( $data, 'search_field', [] );

if ( ! is_string( $search_field['value'] ?? '' ) ) {
	$search_field['value'] = '';
}
?>
<div class="gv-search-box gv-search-field-text">
	<?php if ( ! gv_empty( $search_field['label'], false, false ) ) { ?>
	<label for="search-box-<?php echo esc_attr( $search_field['name'] ); ?>"><?php echo esc_html( $search_field['label'] ); ?></label>
	<?php } ?>
	<p>
		<input type="text" name="<?php echo esc_attr( $search_field['name'] ); ?>" id="search-box-<?php echo esc_attr( $search_field['name'] ); ?>" value="<?php echo esc_attr( $search_field['value'] ); ?>">
	</p>
</div>
