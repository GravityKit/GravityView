<?php
/**
 * Display the search by entry date input boxes
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$search_field     = \GV\Utils::get( $data, 'search_field', [] );
$datepicker_class = \GV\Utils::get( $data, 'datepicker_class', '' );
$custom_class     = \GV\Utils::get( $search_field, 'custom_class', [] );
?>

<div class="gv-search-box gv-search-date <?php echo $custom_class; ?>">
	<?php if ( ! gv_empty( $search_field['label'], false, false ) ) { ?>
	<label for="search-box-<?php echo esc_attr( $search_field['name'] ); ?>"><?php echo esc_html( $search_field['label'] ); ?></label>
	<?php } ?>
	<p>
		<input type="text" name="<?php echo esc_attr( $search_field['name'] ); ?>" id="search-box-<?php echo esc_attr( $search_field['name'] ); ?>" value="<?php echo esc_attr( $search_field['value'] ); ?>" class="<?php echo gravityview_sanitize_html_class( $datepicker_class ); ?>" >
	</p>
</div>
