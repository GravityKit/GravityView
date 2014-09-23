<?php
/**
 * Display the search SELECT input field
 *
 * @see class-search-widget.php
 */

global $gravityview_view;
$view_id = $gravityview_view->view_id;
$search_field = $gravityview_view->search_field;

?>
<div class="gv-search-box">
	<label for=search-box-<?php echo esc_attr( $search_field['name'] ); ?>>
		<?php echo esc_html( $search_field['label'] ); ?>
	</label>
	<p>
		<select name="<?php echo esc_attr( $search_field['name'] ); ?>" id="search-box-<?php echo esc_attr( $search_field['name'] ); ?>">
			<option value="" <?php selected( '', $search_field['value'], true ); ?>>&mdash;</option>
			<?php
			foreach( $search_field['choices'] as $choice ) : ?>
				<option value="<?php echo esc_attr( $choice['value'] ); ?>" <?php selected( $choice['value'], $search_field['value'], true ); ?>><?php echo esc_html( $choice['text'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
</div>