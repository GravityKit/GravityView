<?php
/**
 * Display the search all input box
 *
 * @see class-search-widget.php
 * @global \GV\Template_Context $gravityview
 * @global \GV\Widget           $widget
 * @global \GV\Template         $template
 * @global object               $search_field
 */

$value = $search_field->value;
$label = $search_field->label;

$html_input_type = RGFormsModel::is_html5_enabled() ? 'search' : 'text';
?>

<div class="gv-search-box gv-search-field-text gv-search-field-search_all">
	<div class="gv-search">
	<?php if( ! gv_empty( $label, false, false ) ) { ?>
		<label for="gv_search_<?php echo $gravityview->view->ID; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
		<p><input type="<?php echo $html_input_type; ?>" name="gv_search" id="gv_search_<?php echo $gravityview->view->ID; ?>" value="<?php echo esc_attr( $value ); ?>" /></p>
	</div>
</div>
