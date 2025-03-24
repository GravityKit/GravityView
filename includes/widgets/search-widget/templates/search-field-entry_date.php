<?php
/**
 * Display the search by entry date input boxes
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$view_id = \GV\Utils::get( $data, 'view_id', null );
$search_field = \GV\Utils::get( $data, 'search_field', null );
$value = \GV\Utils::get( $search_field, 'value' );
$label = \GV\Utils::get( $search_field, 'label' );
$datepicker_class = \GV\Utils::get( $data, 'datepicker_class', '' );
?>

<div class="gv-search-box gv-search-date gv-search-date-range gv-search-field-entry_date">
	<?php if ( ! gv_empty( $label, false, false ) ) { ?>
	<label for="gv_start_date_<?php echo (int) $view_id; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<input name="gv_start" id="gv_start_date_<?php echo (int) $view_id; ?>" type="text" class="<?php echo esc_attr( $datepicker_class ); ?>" placeholder="<?php esc_attr_e('Start date', 'gk-gravityview' ); ?>" value="<?php echo $value['start']; ?>">
		<input name="gv_end" id="gv_end_date_<?php echo (int) $view_id; ?>" type="text" class="<?php echo esc_attr( $datepicker_class ); ?>" placeholder="<?php esc_attr_e('End date', 'gk-gravityview' ); ?>" value="<?php echo $value['end']; ?>">
	</p>
</div>
