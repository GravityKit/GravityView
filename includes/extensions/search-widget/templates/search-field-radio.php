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
	<ul>
	<?php foreach( $search_field['choices'] as $choice ) : ?>
		<li>
			<input type="radio" name="<?php echo esc_attr( $search_field['name'] ); ?>" value="<?php echo esc_attr( $choice['value'] ); ?>" id="search-box-<?php echo esc_attr( $search_field['name'] ); ?>" <?php checked( $choice['value'], $search_field['value'], true, 'checked' ); ?>>
			<label for="search-box-<?php echo esc_attr( $search_field['name'] ); ?>"><?php echo esc_html( $choice['text'] ); ?></label>
		</li>
	<?php endforeach; ?>
	</ul>
</div>