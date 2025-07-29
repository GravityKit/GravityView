<?php
/**
 * Display the search by entry date input boxes
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$search_field     = \GV\Utils::get( $data, 'search_field', null );
$value            = \GV\Utils::get( $search_field, 'value', [] );
$label            = \GV\Utils::get( $search_field, 'label' );
$name             = \GV\Utils::get( $search_field, 'name' );
$datepicker_class = \GV\Utils::get( $data, 'datepicker_class', '' );
$custom_class     = \GV\Utils::get( $search_field, 'custom_class', '' );
?>

<div class="gv-search-box gv-search-date gv-search-date-range <?php echo $custom_class; ?>">
	<?php if ( ! gv_empty( $label, false, false ) ) { ?>
		<label for="search-box-<?php echo esc_attr( $name ) . '-start'; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<input name="<?php echo esc_attr( $name ).'[start]'; ?>" id="search-box-<?php echo esc_attr( $name ).'-start'; ?>" type="text" class="<?php echo gravityview_sanitize_html_class( $datepicker_class ); ?>" placeholder="<?php esc_attr_e('Start date', 'gk-gravityview' ); ?>" value="<?php echo esc_attr( $value['start'] ); ?>">
		<input name="<?php echo esc_attr( $name ).'[end]'; ?>" id="search-box-<?php echo esc_attr( $name ).'-end'; ?>" type="text" class="<?php echo gravityview_sanitize_html_class( $datepicker_class ); ?>" placeholder="<?php esc_attr_e('End date', 'gk-gravityview' ); ?>" value="<?php echo esc_attr( $value['end'] ); ?>">
	</p>
</div>
