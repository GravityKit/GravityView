<?php
/**
 * @since 2.0
 *
 * @global array $data
 */

$view_id = \GV\Utils::get( $data, 'view_id', 0 );
$search_mode = \GV\Utils::get( $data, 'search_mode', 'any' );
$search_clear = \GV\Utils::get( $data, 'search_clear', false );
?>
<div class="gv-search-box gv-search-box-submit">
	<?php

	// Output the Clear button, if enabled
	echo $search_clear;

	$args = gv_get_query_args();

	foreach ( $args as $key => $value ) {
		if ( 'gravityview' === $key ) {
			continue;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				?>
				<input type="hidden" name="<?php echo esc_attr( sprintf( '%s[%s]', $key, $k ) ); ?>" value="<?php echo esc_attr( $v ); ?>" />
				<?php
			}
		} else {
			?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<?php
		}
	}

	?>
	<input type="hidden" name="mode" value="<?php echo esc_attr( $search_mode ); ?>" />
	<input type="submit" class="button gv-search-button" id="gv_search_button_<?php echo (int) $view_id; ?>" value="<?php esc_attr_e( 'Search', 'gk-gravityview' ); ?>" />
</div>
