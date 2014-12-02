<?php
/**
 * Display the search Entry ID input box
 *
 * @see class-search-widget.php
 */

global $gravityview_view;
$view_id = $gravityview_view->view_id;
$value = $gravityview_view->search_field['value'];
?>

<div class="gv-search-box">
	<div class="gv-search">
		<label for="gv_entry_id_<?php echo $view_id; ?>"><?php esc_html_e( 'Entry ID:', 'gravityview' ); ?></label>
		<p><input type="text" name="gv_id" id="gv_entry_id_<?php echo $view_id; ?>" value="<?php echo $value; ?>" /></p>
	</div>
</div>