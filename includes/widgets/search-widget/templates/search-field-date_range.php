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

// Safely get start and end values with defaults.
$start_value = $value['start'] ?? '';
$end_value   = $value['end'] ?? '';

// Build input attributes.
$start_input_name = esc_attr( $name ) . '[start]';
$start_input_id   = 'search-box-' . esc_attr( $name ) . '-start';
$end_input_name   = esc_attr( $name ) . '[end]';
$end_input_id     = 'search-box-' . esc_attr( $name ) . '-end';
$input_class      = gravityview_sanitize_html_class( $datepicker_class );
?>

<div class="gv-search-box gv-search-date gv-search-date-range <?php echo esc_attr( $custom_class ); ?>">
	<?php if ( ! gv_empty( $label, false, false ) ) { ?>
		<label for="<?php echo $start_input_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped on line 23. ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<input name="<?php echo $start_input_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped. ?>" id="<?php echo $start_input_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped. ?>" type="text" class="<?php echo $input_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already sanitized. ?>" placeholder="<?php esc_attr_e( 'Start date', 'gk-gravityview' ); ?>" value="<?php echo esc_attr( $start_value ); ?>">
		<input name="<?php echo $end_input_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped. ?>" id="<?php echo $end_input_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped. ?>" type="text" class="<?php echo $input_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already sanitized. ?>" placeholder="<?php esc_attr_e( 'End date', 'gk-gravityview' ); ?>" value="<?php echo esc_attr( $end_value ); ?>">
	</p>
</div>
