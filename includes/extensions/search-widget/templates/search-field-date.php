<?php
/**
 * Display the search by entry date input boxes
 *
 * @see class-search-widget.php
 */

$gravityview_view = GravityView_View::getInstance();
$search_field = $gravityview_view->search_field;

?>

<div class="gv-search-box">
	<label for=search-box-<?php echo esc_attr( $search_field['name'] ); ?>>
		<?php echo esc_html( $search_field['label'] ); ?>
	</label>
	<p>
		<input type="text" name="<?php echo esc_attr( $search_field['name'] ); ?>" id="search-box-<?php echo esc_attr( $search_field['name'] ); ?>" value="<?php echo esc_attr( $search_field['value'] ); ?>" class="<?php echo esc_html( $gravityview_view->datepicker_class ); ?>" >
	</p>
</div>