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

$label = $search_field->label;
$name  = $search_field->name;
$value = $search_field->value;
?>

<div class="gv-search-box gv-search-date gv-search-date-range">
	<?php if( ! gv_empty( $label, false, false ) ) { ?>
	<label for="search-box-<?php echo esc_attr( $name ).'-start'; ?>"><?php echo esc_html( $label ); ?></label>
	<?php } ?>
	<p>
		<input name="<?php echo esc_attr( $name ).'[start]'; ?>" id="search-box-<?php echo esc_attr( $name ).'-start'; ?>" type="text" class="<?php echo esc_html( $widget->datepicker_class ); ?>" placeholder="<?php esc_attr_e('Start date', 'gravityview' ); ?>" value="<?php echo esc_attr( $value['start'] ); ?>">
		<input name="<?php echo esc_attr( $name ).'[end]'; ?>" id="search-box-<?php echo esc_attr( $name ).'-end'; ?>" type="text" class="<?php echo esc_html( $widget->datepicker_class ); ?>" placeholder="<?php esc_attr_e('End date', 'gravityview' ); ?>" value="<?php echo esc_attr( $value['end'] ); ?>">
	</p>
</div>
