<?php
/**
 * Display the search Entry ID input box
 *
 * @see class-search-widget.php
 * @global \GV\Template_Context $gravityview
 * @global \GV\Widget           $widget
 * @global \GV\Template         $template
 * @global object               $search_field
 */

$value = $search_field->value;
$label = $search_field->label;
?>

<div class="gv-search-box gv-search-field-entry_id">
	<div class="gv-search">
		<?php if ( ! gv_empty( $label, false, false ) ) { ?>
		<label for="gv_entry_id_<?php echo $gravityview->view->ID; ?>"><?php echo esc_html( $label ); ?></label>
		<?php } ?>
		<p><input type="text" name="gv_id" id="gv_entry_id_<?php echo $gravityview->view->ID; ?>" value="<?php echo esc_attr( $value ); ?>" /></p>
	</div>
</div>
