<?php
/**
 * Display the search all input box
 *
 * @see class-search-widget.php
 */

global $gravityview_view;
$view_id = $gravityview_view->view_id;
$value = $gravityview_view->search_field['value'];

?>

<div class="gv-search-box">
	<div class="gv-search">
		<label for="gv_search_<?php echo $view_id; ?>"><?php esc_html_e( 'Search Entries:', 'gravityview' ); ?></label>
		<p><input type="text" name="gv_search" id="gv_search_<?php echo $view_id; ?>" value="<?php echo $value; ?>" /></p>
	</div>
</div>