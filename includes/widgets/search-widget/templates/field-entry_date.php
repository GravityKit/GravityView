<?php
/**
 * Display the search by entry date input boxes
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

<div class="gv-search-box gv-search-date gv-search-date-range gv-search-field-entry_date">
	<?php if( ! gv_empty( $label, false, false ) ) { ?>
	<label for="gv_start_date_<?php echo $gravityview->view->ID; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<input name="gv_start" id="gv_start_date_<?php echo $gravityview->view->ID; ?>" type="text" class="<?php echo esc_attr( $widget->datepicker_class ); ?>" placeholder="<?php esc_attr_e('Start date', 'gravityview' ); ?>" value="<?php echo $value['start']; ?>">
		<input name="gv_end" id="gv_end_date_<?php echo $gravityview->view->ID; ?>" type="text" class="<?php echo esc_attr( $widget->datepicker_class ); ?>" placeholder="<?php esc_attr_e('End date', 'gravityview' ); ?>" value="<?php echo $value['end']; ?>">
	</p>
</div>
