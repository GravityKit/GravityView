<?php
/**
 * Display hidden field input
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

$search_field = \GV\Utils::get( $data, 'search_field', null );
?>
<input type="hidden" name="<?php echo esc_attr( $search_field['name'] ?? '' ); ?>" value="<?php echo esc_attr( $search_field['value'] ?? '' ); ?>">
