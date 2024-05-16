<?php
/**
 * Display the search Entry ID input box
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$view_id = \GV\Utils::get( $data, 'view_id', null );
$search_field = \GV\Utils::get( $data, 'search_field', null );
$value = \GV\Utils::get( $search_field, 'value' );
$label = \GV\Utils::get( $search_field, 'label' );
?>

<div class="gv-search-box gv-search-field-entry_id">
	<div class="gv-search">
		<?php if ( ! gv_empty( $label, false, false ) ) { ?>
		<label for="gv_entry_id_<?php echo (int) $view_id; ?>"><?php echo esc_html( $label ); ?></label>
		<?php } ?>
		<p><input type="text" name="gv_id" id="gv_entry_id_<?php echo (int) $view_id; ?>" value="<?php echo esc_attr( $value ); ?>" /></p>
	</div>
</div>
