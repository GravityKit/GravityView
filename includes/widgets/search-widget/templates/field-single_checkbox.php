<?php
/**
 * Display the search single CHECKBOX input field ( on/off type)
 *
 * @see class-search-widget.php
 * @global \GV\Template_Context $gravityview
 * @global \GV\Widget           $widget
 * @global \GV\Template         $template
 * @global object               $search_field
 */
?>

<div class="gv-search-box gv-search-field-single_checkbox">
	<label for="search-box-<?php echo esc_attr( $search_field->name ); ?>" class="gv-check-radio">
		<input type="checkbox" name="<?php echo esc_attr( $search_field->name ); ?>" value="1" id="search-box-<?php echo esc_attr( $search_field->name ); ?>" <?php checked( '1', $search_field->value, true ); ?>>
			<?php if( ! gv_empty( $search_field->label, false, false ) ) { echo esc_html(  $search_field->label ); } ?>
	</label>
</div>
