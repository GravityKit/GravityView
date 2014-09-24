<?php
/**
 * Display the Search widget
 *
 * @see class-search-widget.php
 */

global $gravityview_view, $wp_rewrite;

$view_id = $gravityview_view->view_id;

?>

<form class="gv-widget-search <?php echo gravityview_sanitize_html_class( apply_filters('gravityview_search_class', 'gv-search-horizontal' ) ); ?>" method="get" action="<?php echo esc_url( add_query_arg( array() ) ); ?>">

	<?php
	do_action( 'gravityview_search_widget_fields_before' );

	foreach( $this->search_fields as $search_field ) {
		$gravityview_view->search_field = $search_field;
		$this->render( 'search-field', $search_field['input'] );
	}

	do_action( 'gravityview_search_widget_fields_after' );
	?>

	<?php

	/**
	 *  @deprecated filter gravityview_search_widget_fields
	 *
	 */

		// search filters (fields)
		//echo apply_filters( 'gravityview_search_widget_fields', $gravityview_view->search_fields );

	?>

	<div class="gv-search-box gv-search-box-submit">
		<?php

		// Support default permalink structure
		if( !empty( $_GET['gravityview'] ) && false === $wp_rewrite->using_index_permalinks() ) {
			echo '<input type="hidden" name="gravityview" value="'.esc_attr( $_GET['gravityview'] ).'" />';
		}
		?>
		<input type="submit" class="button gv-search-button" id="gv_search_button_<?php echo $view_id; ?>" value="<?php esc_attr_e( 'Search', 'gravity-view' ); ?>" />
	</div>
</form>
