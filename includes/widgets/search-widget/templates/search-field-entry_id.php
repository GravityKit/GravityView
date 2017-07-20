<?php
/**
 * Display the search Entry ID input box
 *
 * @see class-search-widget.php
 */

$gravityview_view = GravityView_View::getInstance();
$view_id = $gravityview_view->getViewId();
$value = $gravityview_view->search_field['value'];
$label = $gravityview_view->search_field['label'];
?>

<div class="gv-search-box gv-search-field-entry_id">
	<div class="gv-search">
		<?php if( ! gv_empty( $label, false, false ) ) { ?>
		<label for="gv_entry_id_<?php echo $view_id; ?>"><?php echo esc_html( $label ); ?></label>
		<?php } ?>
		<p><input type="text" name="gv_id" id="gv_entry_id_<?php echo $view_id; ?>" value="<?php echo $value; ?>" /></p>
	</div>
</div>