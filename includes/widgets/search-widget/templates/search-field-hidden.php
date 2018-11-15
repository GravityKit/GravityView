<?php
/**
 * Display hidden field input
 *
 * @see class-search-widget.php
 */

$gravityview_view = GravityView_View::getInstance();
$search_field = $gravityview_view->search_field;
?><div><input type="hidden" name="<?php echo esc_attr( $search_field['name'] ); ?>" value="<?php echo esc_attr( $search_field['value'] ); ?>"></div>