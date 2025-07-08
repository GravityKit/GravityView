<?php
/**
 * Display the search by entry date input boxes
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$view_id          = \GV\Utils::get( $data, 'view_id', null );
$search_field     = \GV\Utils::get( $data, 'search_field', null );
$datepicker_class = \GV\Utils::get( $data, 'datepicker_class', '' );
$value            = \GV\Utils::get( $search_field, 'value' );
$label            = \GV\Utils::get( $search_field, 'label' );
$custom_class     = \GV\Utils::get( $search_field, 'custom_class', '' );
$input_type       = \GV\Utils::get( $search_field, 'input_type', 'date_range' );

$is_date_range = 'date_range' === $input_type;
$classes       = [
	'gv-search-box',
	'gv-search-date',
	'gv-search-field-entry_date',
	$custom_class,
];

if ( $is_date_range ) {
	// Insert the date range class after `gv-search-date` to keep the same order as previous versions.
	array_splice( $classes, 2, 0, [ 'gv-search-date-range' ] );
}

$input_html = '<input name="%s" id="%1$s_date_%d" type="text" class="%s" placeholder="%s" value="%s">';
?>

<div class="<?php echo implode( ' ', array_filter( $classes ) ); ?>">
	<?php if ( ! gv_empty( $label, false, false ) ) { ?>
		<label for="gv_start_date_<?php echo (int) $view_id; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<?php
		printf(
			$input_html,
			'gv_start',
			(int) $view_id,
			esc_attr( $datepicker_class ),
			$is_date_range ? esc_attr__( 'Start date', 'gk-gravityview' ) : '',
			esc_attr( $value['start'] ?? '' )
		);

		if ( $is_date_range ) {
			printf(
				$input_html,
				'gv_end',
				(int) $view_id,
				esc_attr( $datepicker_class ),
				esc_attr__( 'End date', 'gk-gravityview' ),
				esc_attr( $value['end'] ?? '' )
			);
		}
		?>
	</p>
</div>
