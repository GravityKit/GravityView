<?php
/**
 * Display the search all input box
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$view_id = \GV\Utils::get( $data, 'view_id', null );
$search_field = \GV\Utils::get( $data, 'search_field', [] );
$value = \GV\Utils::get( $search_field, 'value' );
$label = \GV\Utils::get( $search_field, 'label' );

$html_input_type = 'search';
?>

<div class="gv-search-box gv-search-field-text gv-search-field-search_all">
	<div class="gv-search">
	<?php if ( ! gv_empty( $label, false, false ) ) { ?>
		<label for="gv_search_<?php echo (int) $view_id; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
		<p><input type="<?php echo $html_input_type; ?>" name="gv_search" id="gv_search_<?php echo (int) $view_id; ?>" value="<?php echo esc_attr( $value ); ?>" /></p>
	</div>
</div>
