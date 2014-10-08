<?php
/**
 * Display the search RADIO input field
 *
 * @see class-search-widget.php
 */

global $gravityview_view;
$view_id = $gravityview_view->view_id;
$search_field = $gravityview_view->search_field;

?>
<div class="gv-search-box">

	<?php foreach( $search_field['choices'] as $choice ) : ?>

		<label for="search-box-<?php echo sanitize_html_class( $search_field['name'].$choice['value'].$choice['text'] ); ?>" class="gv-check-radio">
			<input type="radio" name="<?php echo esc_attr( $search_field['name'] ); ?>" value="<?php echo esc_attr( $choice['value'] ); ?>" id="search-box-<?php echo sanitize_html_class( $search_field['name'].$choice['value'].$choice['text'] ); ?>" <?php checked( $choice['value'], $search_field['value'], true ); ?>>
			<?php echo esc_html( $choice['text'] ); ?>
		</label>

	<?php endforeach; ?>

</div>